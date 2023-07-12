<?php

namespace App\Http\Controllers;

use App\Models\Collection;

class CollectionAssetKeyRegisterController extends APIController
{
    /**
     * Load the keyRegisters
     * Get all of the key-registers
     * If asset_id then get the key_registers with the parent/child ids
     * Future - Join on the hirer-key to get the hirer_name and the hirer.created_at/updated_at date to track when assigned.
     */
    public function keyRegisters()
    {
        $assetId = request()->asset_id ?? '';

        request()->request->add(['sort_by' => 'name']);
        $results = Collection::where('collection.slug', 'key-register')
            ->get();

        $results = $results->map([$this, 'mapFieldsToValues']);
        // return $this->successResponse(['data' => $results], 200);

        $assetIds = empty($assetId) ? [] : (new CollectionAssetController)->assetIdList($assetId);

        request()->query->remove('asset_id');

        if (!empty($assetIds)) {
            $results = $results->filter(function ($item) use ($assetIds) {
                return (isset($item->asset_id) && in_array($item->asset_id, $assetIds));
            });
        }
        $allAssets = [];
        $allHirers = [];
        // Get assets with joining bookable field
        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        // Get assets with joining bookable field
        $getHirers = \DB::table('hirer')
            ->whereNull('hirer.deleted_at')
            ->get();

        // Map assets to array with id as key
        foreach ($getHirers as $hirer) {
            $allHirers[$hirer->id] = $hirer;
        }

        $results->map(function ($collection) use ($allAssets, $allHirers) {
            if (!empty($collection->asset_id)) {
                $collection->asset = $allAssets[$collection->asset_id] ?? [];
                if (!empty($collection->asset->parent_id)) {
                    $collection->asset->label = (new AssetController)->getParentName($collection->asset->parent_id, $allAssets) . ' - ' . $collection->asset->name;
                } else {
                    $collection->asset->label = $collection->asset->name;
                }
                if (!empty($collection->asset->parent)) {
                    unset($collection->asset->parent);
                }
            }
            if (!empty($collection->hirer_id)) {
                $collection->hirer = $allHirers[$collection->hirer_id] ?? [];
            }
        });

        return $this->showAll($results);
    }

    /**
     * Load the keyRegisters
     */
    public function hirerKey()
    {
        $results = Collection::where('slug', 'hirer-key')
        // ->with('collections')
        // ->where(function ($where) {
        //     $where->where('parent_id', '')->orWhere('parent_id', null);
        // })
            ->get();
        $results = $results->map([$this, 'mapFieldsToValues']);

        return $this->showAll($results);
    }

    public function formatResults(&$collection)
    {
        // $collection->each(function (&$item) {
        //     if (!empty($item->collections)) {
        //         $children = $this->formatResults($item->collections);
        //         if (!empty($children->count())) {
        //             $item->asset = $children;
        //         }
        //         unset($item->collections);
        //     }
        // });

        return $collection->map([$this, 'mapFieldsToValues']);
    }
}
