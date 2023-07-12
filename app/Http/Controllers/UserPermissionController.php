<?php

namespace App\Http\Controllers;

use App\Models\UserPermission;
use Illuminate\Http\Request;

class UserPermissionController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = UserPermission::get();
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
            'permission_id' => ['required'],
        ]);
        $values = [
            'user_id' => $request->user_id,
            'permission_id' => $request->permission_id];

        $new = UserPermission::updateOrCreate($values);
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
        return $this->showOne(UserPermission::where('id', $id)->get()->map([$this, 'mapFieldsToValues'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserPermission  $userPermission
     * @return \Illuminate\Http\Response
     */
    public function edit(UserPermission $userPermission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserPermission  $userPermission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserPermission $userPermission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserPermission  $userPermission
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserPermission $userPermission)
    {
        $userPermission->delete();
        $this->clearCache();

        return response()->json(['data' => 'Deleted', $userPermission->id]);
    }
}
