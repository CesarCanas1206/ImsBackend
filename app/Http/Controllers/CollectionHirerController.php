<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionHirerController extends APIController
{
    /**
     * Load the hirers
     */
    public function hirers()
    {
        request()->request->add(['sort_by' => 'name']);
        $results = Collection::where('slug', 'hirer')
        // ->with('collections')
        // ->where(function ($where) {
        //     $where->where('parent_id', '')->orWhere('parent_id', null);
        // })
            ->get();

        $results = $results->map([$this, 'mapFieldsToValues']);

        return $this->showAll($results);
    }

    public function assetHirer()
    {
        $results = Collection::where('slug', 'asset-hirer')
            ->get();

        $results->map([$this, 'mapFieldsToValues']);

        if (request()->has('asset_id')) {
            $assetId = request()->asset_id;
        }

        $hirers = [];
        if ($assetId) {
            $results = $results->filter(function ($item) use ($assetId) {
                return $item->asset_id == $assetId;
            });
            $hirerIds = array_column($results->toArray(), 'hirer_id');
            $hirers = Collection::where('slug', 'hirer')
                ->get();
            $hirers = array_values($hirers->map([$this, 'mapFieldsToValues'])
                    ->filter(function ($item) use ($hirerIds) {
                        return in_array($item->id, $hirerIds);
                    })->toArray());
        }

        $assets = (new CollectionAssetController)->assets();

        if (!empty($assets)) {
            $results = array_values($results->filter(function ($item) use ($assets) {
                return in_array($item->asset_id, array_column($assets, 'id'));
            })->toArray());
        }

        if (!empty($assets)) {
            $results = collect(array_map(function ($item) use ($assets, $hirers) {
                $assetArray = array_values(array_filter($assets, function ($asset) use ($item) {
                    return $asset['id'] == $item['asset_id'];
                }));
                $hirerArray = array_values(array_filter($hirers, function ($hirer) use ($item) {
                    return $hirer['id'] == $item['hirer_id'];
                }));
                $item['label'] = $assetArray[0]['label'] ?? $assetArray[0]['name'];
                $item['name'] = $hirerArray[0]['name'] ?? '';
                return $item;
            }, $results));
        }

        request()->query->add(['asset_id' => $assetId]);
        return $this->showAll($results);
    }
}
