<?php

namespace App\Http\Controllers;

use App\Models\UserEmail;
use Illuminate\Http\Request;

class UserEmailController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = UserEmail::get();
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
            'user_id' => 'required',
            'email_id' => ['required'],
        ]);
        $values = [
            'user_id' => $request->user_id,
            'email_id' => $request->email_id];

        $new = UserEmail::updateOrCreate($values);
        $this->addResponses($request, $new->id, UserPermission::class);
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
        return $this->showOne(UserEmail::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserEmail  $userEmail
     * @return \Illuminate\Http\Response
     */
    public function edit(UserEmail $userEmail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserEmail  $userEmail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserEmail $userEmail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserEmail  $userEmail
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserEmail $userEmail)
    {
        $userEmail->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $userEmail->id]);
    }
}
