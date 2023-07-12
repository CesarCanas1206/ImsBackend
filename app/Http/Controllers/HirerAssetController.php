<?php

namespace App\Http\Controllers;

use App\Models\HirerAsset;
use Illuminate\Http\Request;

class HirerAssetController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get user asset link for current hirer
        if (!empty(request()->hirer_id)) {
            $results = HirerAsset::without(['hirer'])->where('hirer_id', request()->hirer_id)->get();
            request()->query->remove('hirer_id');
        } else {
            $results = HirerAsset::get();
        }

        $results = $results->map([$this, 'mapFieldsToValues']);

        // Get assets
        $getAssets = \DB::table('asset')
            ->whereNull('asset.deleted_at')
            ->get();

        // Map assets to array with id as key
        foreach ($getAssets as $asset) {
            $allAssets[$asset->id] = $asset;
        }

        $results->map(function ($collection) use ($allAssets) {
            if (!empty($collection->asset)) {
                $collection->asset = $this->mapFieldsToValues($collection->asset);
                if (!empty($collection->asset->parent_id)) {
                    $collection->asset->label = (new AssetController)->getParentName($collection->asset->parent_id, $allAssets) . ' - ' . $collection->asset->name;
                } else {
                    $collection->asset->label = $collection->asset->name;
                }
                if (!empty($collection->asset->parent)) {
                    unset($collection->asset->parent);
                    // $collection->asset->parent = $this->mapFieldsToValues($collection->asset->parent);
                }
            }
            if (!empty($collection->hirer)) {
                $collection->hirer = $this->mapFieldsToValues($collection->hirer);
            }
        });

        return $this->showAll($results);
    }

    public function mapFurther($collection)
    {
        if (!empty($collection->asset)) {
            $collection->asset = $this->mapFieldsToValues($collection->asset);
            if (!empty($collection->asset->parent)) {
                $collection->asset->parent = $this->mapFieldsToValues($collection->asset->parent);
            }
        }
        if (!empty($collection->hirer)) {
            $collection->hirer = $this->mapFieldsToValues($collection->hirer);
        }

        return $collection;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'hirer_id' => 'required',
            'asset_id' => ['required'],
        ]);
        $values = [
            'hirer_id' => $request->hirer_id,
            'asset_id' => $request->asset_id];

        $new = HirerAsset::updateOrCreate($values);
        $this->addResponses($request, $new->id, HirerAsset::class);
        $this->clearCache();
        return $this->show($new->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->showOne(HirerAsset::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HirerAsset  $hirerAsset
     * @return \Illuminate\Http\Response
     */
    public function edit(HirerAsset $hirerAsset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HirerAsset  $hirerAsset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HirerAsset $hirerAsset)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HirerAsset  $hirerAsset
     * @return \Illuminate\Http\Response
     */
    public function destroy(HirerAsset $hirerAsset)
    {
        $hirerAsset->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $hirerAsset->id]);
    }
}
