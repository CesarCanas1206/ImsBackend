<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionField;
use App\Models\User;

class CollectionBookingController extends APIController
{
    /**
     * Load the data of casual bookings
     */
    public function data()
    {
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse(['data' => $cached]);
        }
        // if (request()->has('with') && request()->with == 'asset') {
        //     return $this->assetList();
        // }
        $assetId = request()->asset_id ?? '';

        request()->request->add(['with' => 'usage', 'sort_by' => 'name']);
        $results = Collection::where('slug', 'booking')
            ->with('collections')
        // ->where(function ($where) {
        //     if (request()->has('asset_id')) {
        //         //limit the assets to parent and immediate children.
        //         $assetId = request()->asset_id;
        //         $where->where('id', $assetId)->orWhere('parent_id', $assetId);
        //         request()->query->remove('asset_id');
        //     }
        // })
            ->get();

        // $collectionController = new CollectionController();
        // $results = $results->map([$collectionController, 'formatFields']);

        // $results = $results->map([$this, 'mapFieldsToValues']);
        $this->formatResults($results);

        $assetIds = empty($assetId) ? [] : (new CollectionAssetController)->assetIdList($assetId);

        request()->query->remove('asset_id');

        if (!empty($assetIds)) {
            $results = $results->filter(function ($item) use ($assetIds) {
                if (isset($item->usage)) {
                    return count(($item->usage->filter(function ($usage) use ($assetIds) {
                        return (isset($usage->asset_id) && in_array($usage->asset_id, $assetIds));
                    })));
                    return $item;
                }
            });
        }

        return $this->showAll($results);
    }

    public function formatResults(&$collection)
    {
        $collection->each(function (&$item) {
            $this->withParent($item);
            if (!empty($item->collections)) {
                $children = $this->formatResults($item->collections);
                if (!empty($children->count())) {
                    $item->usage = $children;
                }
                unset($item->collections);
            }
        });

        return $collection->map([$this, 'mapFieldsToValues']);
    }

    public function withParent(&$collection)
    {
        if (request()->has('withParent') && !empty($collection->parent_id)) {
            if (!isset($this->tmpLoaded[$collection->parent_id])) {
                $parent = CollectionField::where('collection_id', $collection->parent_id)
                    ->where('reference', 'name')
                    ->pluck('value')
                    ->first();
                if ($parent) {
                    $check = CollectionField::where('collection_id', $collection->parent_id)
                        ->where('reference', 'user_id')
                        ->pluck('value')
                        ->first();

                    if (!empty($check)) {
                        $getUser = User::where('id', $check)->select('name', 'email')->first();
                        if (!empty($getUser)) {
                            $collection->user = $getUser;
                            $this->tmpLoaded['user'][$collection->parent_id] = $getUser;
                        }
                    }

                    $collection->parent = $parent;
                }
                $this->tmpLoaded[$collection->parent_id] = $parent;
            } else {
                $collection->parent = $this->tmpLoaded[$collection->parent_id];
                if (isset($this->tmpLoaded['user'][$collection->parent_id])) {
                    $collection->user = $this->tmpLoaded['user'][$collection->parent_id];
                }
            }
        }
    }

    public function flattenCollection($collection, $parentId = '')
    {
        $flattenedCollection = collect();

        foreach ($collection as $item) {
            if ($item->id == $parentId || $item->parent_id == $parentId) {
                $flattenedCollection->push($item);

                // Append any children
                if (!empty($item->collections)) {
                    $children = $this->flattenCollection($item->collections, $item->id);
                    $flattenedCollection = $flattenedCollection->merge($children);
                    unset($item->collections);
                }
            }
        }

        return $flattenedCollection->map([$this, 'mapFieldsToValues']);
    }
}
