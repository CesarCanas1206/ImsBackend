<?php

namespace App\Http\Controllers;

use App\Models\AssetForm;
use Illuminate\Http\Request;

class AssetFormController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = AssetForm::get();
        $results = $results->map([$this, 'mapFieldsToValues']);
        return $this->showAll($results);
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
        $request->validate([
            'asset_id' => 'required',
            'form_id' => ['required'],
        ]);

        $values = [
            'asset_id' => $request->asset_id,
            'form_id' => $request->form_id];

        $new = AssetForm::updateOrCreate($values);
        $this->addResponses($request, $new->id, UserAsset::class);
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
        return $this->showOne(AssetForm::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AssetForm  $assetForm
     * @return \Illuminate\Http\Response
     */
    public function edit(AssetForm $assetForm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssetForm  $assetForm
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssetForm $assetForm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AssetForm  $assetForm
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssetForm $assetForm)
    {
        $assetForm->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $assetForm->id]);
    }
}
