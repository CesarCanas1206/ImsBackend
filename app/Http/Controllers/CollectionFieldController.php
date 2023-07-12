<?php

namespace App\Http\Controllers;

use App\Models\CollectionField;
use Illuminate\Http\Request;

class CollectionFieldController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CollectionField $collectionField)
    {
        return $this->showAll($collectionField);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $collectionField = CollectionField::where('inspection_id', $request->inspection_id)->where('reference', $request->reference);
        // if (!empty($collectionField)) {
        //     $collectionField->fill($request->all());

        //     $collectionField->save();

        //     return $this->showOne($collectionField);
        // }
        $check = ['collection_id' => $request->inspection_id, 'reference' => $request->reference];
        $new = CollectionField::updateOrCreate($check, $request->all());

        $this->clearCache();

        return $this->showOne($new, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CollectionField  $collectionField
     * @return \Illuminate\Http\Response
     */
    public function show(CollectionField $collectionField)
    {
        return $this->showOne($collectionField);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CollectionField  $collectionField
     * @return \Illuminate\Http\Response
     */
    public function update(CollectionField $collectionField, Request $request)
    {
        $collectionField->fill($request->all());

        $collectionField->save();

        $this->clearCache();

        return $this->showOne($collectionField);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CollectionField  $collectionField
     * @return \Illuminate\Http\Response
     */
    public function destroy(CollectionField $collectionField)
    {
        //
    }
}
