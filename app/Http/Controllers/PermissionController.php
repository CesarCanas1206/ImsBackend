<?php

namespace App\Http\Controllers;

use App\Http\Controllers\APIController;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->showAll(
            Permission::select(['id', 'name', 'code', 'category', 'enabled'])
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        $this->validate($request, $rules);

        $data = $request->all();

        $permission = Permission::create($data);

        $this->clearCache();

        return $this->showOne($permission, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        $permission = Permission::findOrFail($permission->id);

        return $this->showOne($permission);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $permission->fill($request->all());

        if ($permission->isClean()) {
            return $this->errorResponse('The values are the same', 422);
        }
        $permission->save();

        $this->clearCache();

        $permission = Permission::findOrFail($permission->id);

        return $this->showOne($permission);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Activity\Activity  $activity
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //Delete new added page
        return Permission::destroy($id);
    }
}
