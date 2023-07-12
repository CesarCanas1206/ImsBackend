<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\APIController;
use App\Models\CollectionField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends APIController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = User::with(['role', 'fields'])->orderBy('first_name')->get([
            'id',
            'name',
            'first_name',
            'last_name',
            'email',
            'enabled',
            'archived',
            'deleted_at',
            'last_login',
            'role_id',
        ]);
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
        if (!empty($request->get('password'))) {
            $request->merge([
                'password' => Hash::make($request->password),
            ]);
        }
        $user = User::create($request->all());

        /** TODO - get the role id and get role_emails and add for this user in user_email */
        // $roleid = 1;
        // select * from role_email where roleid = 1
        // insert into user_email from above
        $this->addResponses($request, $user->id, User::class);
        $this->clearCache();

        return $this->showOne($user);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $result = $this->mapFieldsToValues($user);
        return $this->showOne($result);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        if (!empty($request->get('password'))) {
            $request->merge([
                'password' => Hash::make($request->password),
            ]);
        }

        $user->fill($request->all())->save();
        $this->addResponses($request, $user->id, User::class);
        $this->clearCache();
        return $this->show($user);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        User::where('id', $user->id)->first()->delete();

        CollectionField::where('collection_id', $user->id)->delete();

        $this->clearCache();

        return response()->json(['data' => 'Deleted', $user->id]);
    }

    /**
     * Validate if the email address has not been used
     */
    public function validateEmail(Request $request)
    {
        $email = $request->get('email');
        $id = $request->get('id');

        $user = User::where('email', 'like', $email)->first();
        // return response()->json(['data' => $user]);

        if ($user) {
            if ($user->id == $id) {
                return response()->json(['data' => 'OK', 'status' => 'OK']);
            } else {
                return response()->json(['data' => 'Email address already in use', 'status' => 'error']);
            }
        } else {
            return response()->json(['data' => 'OK']);
        }
    }

    public function restore($id)
    {
        $user = User::withTrashed()->find($id);
        if ($user && $user->trashed()) {
            $user->restore();
        }

        return response()->json(['data' => 'Restored', $user->id]);
    }
}
