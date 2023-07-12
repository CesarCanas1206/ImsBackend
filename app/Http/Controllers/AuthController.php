<?php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\User;
use App\Notifications\PasswordResetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details',
            ], 401);
        }

        $user = User::where('email', $request['email'])
            ->leftJoin('role', 'role.id', 'role_id')
        // ->leftJoin('page', 'page.id', 'role.default_page_id')
            ->select(['users.*', 'role.default_page_id', 'role.permissions'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        $permissions = Permission::select(['code'])->pluck('code');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->email,
            'id' => $user->id,
            'page' => $user->default_page_id ?: 'dashboard',
            'permissions' => !empty($user->permissions) ? json_decode($user->permissions, true) : $permissions,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'We have emailed your password reset link!',
            ], 404);
        }

        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Str::random(60),
            ]
        );
        // $passwordReset = PasswordReset::updateOrCreate(
        //     ['email' => $user->email],
        //     [
        //         'email' => $user->email,
        //         'token' => Str::random(60),
        //     ]
        // );
        if ($user && $passwordReset) {
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        }

        return response()->json([
            'message' => 'We have emailed your password reset link!',
        ]);

        // $validatedData = $request->validate([
        //     'email' => 'required|string|email|max:255',
        // ]);

        // $user = User::where('email', $validatedData['email'])->first();

        // $token = $user->createToken('auth_token')->plainTextToken;

        // if (!$user) {
        //     return response()->json([
        //         'message' => 'Sent',
        //     ], 401);
        // }

        // return response()->json([
        //     'message' => 'Sent',
        //     'token' => $token,
        // ]);
        // $token = $user->createToken('auth_token')->plainTextToken;

        // return response()->json([
        //     'access_token' => $token,
        //     'token_type' => 'Bearer',
        // ]);
    }

    /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|confirmed',
            'token' => 'required|string',
        ]);
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email],
        ])->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'This password reset token is invalid.',
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'We can\'t find a user with that e-mail address.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        $passwordReset->delete();
        // $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json([
            'message' => 'The password has been reset!',
            'result' => 'success',
        ]);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function checkToken($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->select('email')
            ->first();
        if (!$passwordReset) {
            return response()->json([
                'message' => 'This password reset token is invalid.',
            ], 404);
        }

        // if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
        //     $passwordReset->delete();
        //     return response()->json([
        //         'message' => 'This password reset token is invalid.',
        //     ], 404);
        // }
        return response()->json($passwordReset);
    }

    public function logout(Request $request)
    {
        if (!$request->user()) {
            return [
                'message' => 'Aready logged out',
            ];
        }
        $request->user()->currentAccessToken()->delete();
        return [
            'message' => 'logged out',
        ];
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
