<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionAssetControllerOld extends APIController
{
    /**
     * Load the assets with sub assets (recursively)
     */
    public function assets()
    {
        if (request()->has('with') && request()->with == 'asset') {
            return $this->assetList();
        }
        request()->request->add(['sort_by' => 'name']);
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse(['data' => $cached]);
        }

        $results = Collection::where('slug', 'asset')
            ->get();

        $results = $results->map([$this, 'mapFieldsToValues']);

        if (request()->has('asset_id')) {
            $assetId = request()->asset_id;
            request()->query->remove('asset_id');
        }

        //limit the assets to parent and immediate children.
        $assetIds = empty($assetId) ? [] : $this->assetIdList($assetId);

        if (!empty($assetIds)) {
            $results = $results->filter(function ($item) use ($assetIds) {
                return (in_array($item->id, $assetIds));
            });
        }
        $this->createHierarchyLabel($results);

        if (!empty($assetIds)) {
            return array_values($results->toArray());
        }

        return $this->showAll($results);
    }

    /**
     * Load the assets with sub assets (recursively)
     */
    public function assetList()
    {
        request()->request->add(['with' => 'asset', 'sort_by' => 'name']);
        $cached = $this->hasCache();
        if ($cached) {
            return $this->successResponse(['data' => $cached]);
        }
        $results = Collection::where('slug', 'asset')
            ->with('collections')
            ->where(function ($where) {
                $where->where('parent_id', '')->orWhere('parent_id', null);
            })
            ->get();

        $this->formatResults($results);

        return $this->showAll($results);
    }

    public function formatResults(&$collection)
    {
        $collection->each(function (&$item) {
            if (!empty($item->collections)) {
                $children = $this->formatResults($item->collections);
                if (!empty($children->count())) {
                    $item->asset = $children;
                }
                unset($item->collections);
            }
        });

        return $collection->map([$this, 'mapFieldsToValues']);
    }

    /**
     * Load the assets with sub assets (recursively)
     *
     */
    public function assetSingleList()
    {
        request()->request->add(['with' => 'asset', 'sort_by' => 'name']);
        $results = Collection::where('slug', 'asset')
            ->with('collections')
            ->where(function ($where) {
                $where->where('parent_id', '')->orWhere('parent_id', null);
            })->get();

        $results = $this->flattenCollection($results, request()->asset_id);
        request()->query->remove('asset_id');
        $this->createHierarchyLabel($results);

        return $this->showAll($results);
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

    public function createHierarchyLabel(&$collection)
    {
        $collection->each(function (&$item) use ($collection) {
            if (!empty($item->parent_id)) {
                $label = $this->recursiveNameBuilder($collection->toArray(), $item->id);
                $item->label = $label;
            }
        });
    }

    public function recursiveNameBuilder($arr, $id = '')
    {
        $item = array_filter($arr, function ($x) use ($id) {
            return $x['id'] === $id;
        });
        $item = count($item) ? array_values($item)[0] : [];

        if (empty($item) || !array_key_exists('parent_id', $item)) {
            return $item['name'] ?? null;
        }
        $parent = $this->recursiveNameBuilder($arr, $item['parent_id']);
        if (empty($parent)) {
            return $item['name'];
        }
        return $parent . ' - ' . $item['name'];
    }

    /**
     * Load the assetIds with sub assets (recursively)
     */
    public function assetIdList($asset_id = '')
    {
        $assetId = !empty($asset_id) ? $asset_id : '';
        if (empty($assetId) && request()->has('asset_id')) {
            $assetId = request()->asset_id;
            request()->query->remove('asset_id');
        }

        $results = Collection::where('slug', 'asset')
            ->get();

        $ids = $this->getIds($results, $assetId);

        if (!empty($asset_id)) {
            return $ids;
        }
        $results = $results->map([$this, 'mapFieldsToValues'])->filter(function ($item) use ($ids) {
            return (in_array($item->id, $ids));
        });

        $this->createHierarchyLabel($results);
        return $this->showAll($results);
    }

    public function getIds($collection, $id)
    {

        $childIds = $collection
            ->where('parent_id', $id)
            ->pluck('id');

        $ids = [$id];

        foreach ($childIds as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getIds($collection, $childId));
        }

        return array_unique(array_filter($ids));
    }
}
