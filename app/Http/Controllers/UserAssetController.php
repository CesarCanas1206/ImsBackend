<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\UserAsset;
use Illuminate\Http\Request;

class UserAssetController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = UserAsset::get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        return $this->showAll($results);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'asset_id' => ['required'],
        ]);

        $values = [
            'user_id' => $request->user_id,
            'asset_id' => $request->asset_id];

        $new = UserAsset::updateOrCreate($values);
        $this->addResponses($request, $new->id, UserAsset::class);

        // Add child assets (if available)
        $children = Asset::where('parent_id', $request->asset_id)->get();
        foreach ($children as $child) {
            $values = [
                'user_id' => $request->user_id,
                'asset_id' => $child->id];
            $newChild = UserAsset::updateOrCreate($values);
            $this->addResponses($request, $newChild->id, UserAsset::class);
        }

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
        return $this->showOne(UserAsset::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserAsset $userAsset)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserAsset  $userAsset
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserAsset $userAsset)
    {
        $userAsset->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $userAsset->id]);
    }
}
