<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;

class InspectionController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->showAll(Inspection::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $new = Inspection::create($request->all());
        $this->addResponses($request, $new->id, Inspection::class);
        $this->clearCache();
        return $this->show($new);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Inspection  $inspection
     * @return \Illuminate\Http\Response
     */
    public function show(Inspection $inspection)
    {
        return $this->showOne($inspection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inspection  $inspection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Inspection $inspection)
    {
        $inspection->fill($request->all())->save();

        $this->addResponses($request, $inspection->id, Inspection::class);

        $this->clearCache();
        return $this->show($inspection);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Inspection  $inspection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Inspection $inspection)
    {
        $inspection->delete();

        CollectionField::where('collection_id', $inspection->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $inspection->id]);
    }
}
