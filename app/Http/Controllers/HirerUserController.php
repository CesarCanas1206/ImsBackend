<?php

namespace App\Http\Controllers;

use App\Models\HirerUser;
use Illuminate\Http\Request;

class HirerUserController extends APIController
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = HirerUser::get();
        $results = $results->map(function ($collection) {
            if (!empty($collection->user)) {
                $collection->user = $this->mapFieldsToValues($collection->user);;
            }
            return $collection;
        });

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
        request()->validate([
            'hirer_id' => 'required',
            'user_id' => ['required'],
        ]);
        $values = [
            'hirer_id' => $request->hirer_id,
            'user_id' => $request->user_id];

        $new = HirerUser::updateOrCreate($values);
        $this->addResponses($request, $new->id, HirerUser::class);
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
        return $this->showOne(HirerUser::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HirerUser  $hirerAsset
     * @return \Illuminate\Http\Response
     */
    public function edit(HirerUser $hirerUser)
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
    public function update(Request $request, HirerUser $hirerUser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HirerAsset  $hirerAsset
     * @return \Illuminate\Http\Response
     */
    public function destroy(HirerUser $hirerUser)
    {
        $hirerUser->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $hirerUser->id]);
    }
}
