<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CollectionField;
use App\Models\HirerAsset;
use App\Models\UserAsset;
use Illuminate\Http\Request;

class AssetController extends APIController
{
    public function index()
    {
        $assetId = request()->asset_id ?? '';
        $results = new Asset();

        if (request()->has('parent_id')) {
            $parentId = request()->parent_id;
            if ($parentId === 'empty') {
                $results = $results->whereNull('parent_id');
            }
        }

        if (!empty(request()->user()) && request()->user()->id) {
            $hasLinks = UserAsset::where('user_id', request()->user()->id)->count();
            if (!empty($hasLinks)) {
                $results = $results->join('user_asset', 'user_asset.asset_id', '=', 'asset.id')
                    ->where('user_asset.user_id', request()->user()->id);
                $results = $results->select('asset.*');
            }
        }

        $assetIds = empty($assetId) ? [] : (new CollectionAssetController)->assetIdList($assetId);

        request()->query->remove('asset_id');

        $results = $results->get();

        if (!empty($assetIds)) {
            $results = $results->filter(function ($item) use ($assetIds) {
                return (in_array($item->id, $assetIds));
            });
        }

        $results = $results->map([$this, 'mapFieldsToValues']);

        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        $results->map(function ($collection) use ($allAssets) {
            if (!empty($collection->parent_id)) {
                $collection->label = $this->getParentName($collection->parent_id, $allAssets) . ' - ' . $collection->name;
            } else {
                $collection->label = $collection->name;
            }
            if (!empty($collection->parent)) {
                $collection->parent = $this->mapFieldsToValues($collection->parent);
            }
        });
        //$results = $results->map([$this, 'mapFurther']);
        return $this->showAll($results);
    }

    public function show(Asset $asset)
    {
        $result = $this->mapFieldsToValues($asset);
        $result = $this->mapFurther($result);
        $result = $this->mapChildren($result);
        return $this->showOne($result);
    }

    public function mapFurther($collection)
    {
        if (!empty($collection->parent)) {
            $collection->parent = $this->mapFieldsToValues($collection->parent);
        }
        return $collection;
    }

    public function mapChildren($collection)
    {
        if (!empty($collection->id)) {
            $results = Asset::without('parent')
                ->without('fields')
                ->where('parent_id', $collection->id)
                ->get()
                ->map([$this, 'mapFieldsToValues']);
            $collection->childrens = $results;
        }
        return $collection;
    }

    public function store(Request $request)
    {
        $new = Asset::create($request->all());
        $this->addResponses($request, $new->id, Asset::class);
        $this->clearCache();
        return $this->show($new);
    }

    public function update(Request $request, Asset $asset)
    {
        $asset->fill($request->all())->save();

        $this->addResponses($request, $asset->id, Asset::class);

        $this->clearCache();
        return $this->show($asset);
    }

    public function destroy(Request $request, Asset $asset)
    {
        $asset->delete();

        CollectionField::where('collection_id', $asset->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $asset->id]);
    }

    /**
     * Recursively get name of parent asset from given parent_id
     */
    public function getParentName($parentId, $assets = [])
    {
        $asset = $assets[$parentId] ?? null;
        if (empty($asset)) {
            return '';
        }
        $parentName = $asset->name;
        if (!empty($asset->parent_id)) {
            $parentName = $this->getParentName($asset->parent_id, $assets) . ' - ' . $parentName;
        }
        return $parentName;
    }

    /**
     * Return list of inspection assets (assets with a link in asset_form)
     */
    public function inspection()
    {
        $userAssets = [];
        $allAssets = [];
        $assetForms = [];
        $results = [];

        // Get user asset link for current user (if logged in)
        if (!empty(request()->user()) && request()->user()->id) {
            $userAssetLink = UserAsset::without(['user', 'asset'])->where('user_id', request()->user()->id)->get();
            foreach ($userAssetLink as $userAsset) {
                $userAssets[] = $userAsset->asset_id;
            }
        }

        // Get assets with joining inspectable field
        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->leftJoin('collection_field', function ($join) {
                $join->on('collection_field.collection_id', '=', 'asset.id')
                    ->where('collection_field.reference', '=', 'inspectable');
            })
            ->select(['collection_field.value as inspectable', 'asset.*'])
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        // Get asset form links
        $getAssetForms = \App\Models\AssetForm::without(['asset', 'form'])
            ->get(['asset_id', 'form_id', 'id']);

        // Map asset form links to array with asset_id as key
        foreach ($getAssetForms as $assetForm) {
            $assetForms[$assetForm->asset_id][] = $assetForm;
        }

        // Loop through asset forms and add to results if inspectable - and set up parent name as label
        foreach ($assetForms as $assetId => $assetForm) {
            if (empty($userAssets) || in_array($assetId, $userAssets)) {
                $asset = $allAssets[$assetId] ?? null;
                if (empty($asset) || (!empty($asset->inspectable) && !in_array($asset->inspectable, ['Yes', '1']))) {
                    continue;
                }
                if (!empty($asset->parent_id)) {
                    $asset->label = $this->getParentName($asset->parent_id, $allAssets) . ' - ' . $asset->name;
                } else {
                    $asset->label = $asset->name;
                }
                $asset->asset_forms = $assetForm;

                $results[] = $asset;
            }
        }

        // Collect to be able to run through showAll
        $results = \Collect($results);
        return $this->showAll($results);
    }
    /**
     * Return list of bookable assets that a hirer can book
     */
    public function bookable()
    {
        $hirerAssets = [];
        $allAssets = [];
        $bookableAssets = [];
        $results = [];

        // Get user asset link for current hirer
        if (!empty(request()->hirer_id)) {
            $hirerAssetLink = HirerAsset::without(['hirer', 'asset'])->where('hirer_id', request()->hirer_id)->get();
            foreach ($hirerAssetLink as $hirerAsset) {
                $hirerAssets[] = $hirerAsset->asset_id;
            }
            //unset the hirer_id, otherwise it gets filtered out.
            request()->query->remove('hirer_id');
        }

        // Get assets with joining bookable field
        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->leftJoin('collection_field', function ($join) {
                $join->on('collection_field.collection_id', '=', 'asset.id')
                    ->where('collection_field.reference', '=', 'bookable');
            })
            ->select(['collection_field.value as bookable', 'asset.*'])
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        // Loop through and add to results if bookable - and set up parent name as label
        foreach ($allAssets as $asset) {
            if (!empty($asset->parent_id)) {
                $asset->label = $this->getParentName($asset->parent_id, $allAssets) . ' - ' . $asset->name;
            } else {
                $asset->label = $asset->name;
            }
            if (!empty($asset->bookable) && in_array($asset->bookable, ['Yes', '1'])) {
                $bookableAssets[$asset->id] = $allAssets[$asset->id] ?? null;
            }
        }
        if (!empty($hirerAssets)) {
            foreach ($hirerAssets as $assetId) {
                if (isset($bookableAssets[$assetId])) {
                    $results[] = $bookableAssets[$assetId];
                }
            }
        } else {
            $results = array_values($bookableAssets);
        }

        // Collect to be able to run through showAll
        $results = \Collect($results);
        return $this->showAll($results);
    }

    public function getAssetChildren($assetId, $assets)
    {
        $children = [];
        foreach ($assets as $asset) {
            if ($asset->parent_id == $assetId) {
                $getChildren = $this->getAssetChildren($asset->id, $assets);
                if (!empty($getChildren)) {
                    $asset->assets = $getChildren;
                }
                $children[] = $asset;
            }
        }
        return $children;
    }

    /**
     * Return list of assets for the venue listing page
     */
    public function venueList()
    {
        $userAssets = [];
        $allAssets = [];
        $assetForms = [];
        $results = [];

        // Get user asset link for current user (if logged in)
        if (!empty(request()->user()) && request()->user()->id) {
            $userAssetLink = UserAsset::without(['user', 'asset'])->where('user_id', request()->user()->id)->get();
            foreach ($userAssetLink as $userAsset) {
                $userAssets[] = $userAsset->asset_id;
            }
        }

        // Get assets and map to allAssets
        Asset::without('parent')
            ->get()
            ->map([$this, 'mapFieldsToValues'])
            ->sortBy->name
        // Map assets to array with id as key
            ->each(function ($asset) use (&$allAssets) {
                $allAssets[$asset->id] = $asset;
            });

        /** For assets the use has access to, add the label (with parent name) and get the children */
        foreach ($allAssets as $key => $asset) {
            if (!empty($userAssets) && !in_array($asset->id, $userAssets)) {
                continue;
            }
            if (!empty($asset->parent_id)) {
                $asset->label = $this->getParentName($asset->parent_id, $allAssets) . ' - ' . $asset->name;
            } else {
                $asset->label = $asset->name;
            }
            $getChildren = $this->getAssetChildren($asset->id, $allAssets);
            if (!empty($getChildren)) {
                $asset->assets = $getChildren;
            }
            $allAssets[$key] = $asset;
        }

        /** For venue listing page, only return top level venues (children will be added as assets) */
        $results = array_filter($allAssets, function ($asset) {
            return empty($asset->parent_id);
        });

        // Collect to be able to run through showAll
        $results = \Collect($results);
        return $this->showAll($results);
    }

    public function assetEquipments($parent_id)
    {
        $results = [];
        if (!empty($parent_id)) {
            $results = CollectionField::where('collection_id', $parent_id)
                ->where('reference', 'equipments')
                ->pluck('value')
                ->first();
        }
        return $this->successResponse(['data' => json_decode($results)]);
    }
}
