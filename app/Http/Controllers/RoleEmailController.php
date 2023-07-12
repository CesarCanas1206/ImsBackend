<?php

namespace App\Http\Controllers;

use App\Models\RoleEmail;
use Illuminate\Http\Request;

class RoleEmailController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = RoleEmail::get();
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
        request()->validate([
            'role_id' => 'required',
            'email_id' => ['required'],
        ]);
        $values = [
            'role_id' => $request->role_id,
            'email_id' => $request->email_id];

        $new = RoleEmail::updateOrCreate($values);
        $this->addResponses($request, $new->id, RoleEmail::class);
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
        return $this->showOne(RoleEmail::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RoleEmail  $roleEmail
     * @return \Illuminate\Http\Response
     */
    public function edit(RoleEmail $roleEmail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RoleEmail  $roleEmail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RoleEmail $roleEmail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RoleEmail  $roleEmail
     * @return \Illuminate\Http\Response
     */
    public function destroy(RoleEmail $roleEmail)
    {
        $roleEmail->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $roleEmail->id]);
    }
}
