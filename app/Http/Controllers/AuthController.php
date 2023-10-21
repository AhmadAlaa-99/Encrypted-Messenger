<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $data['email'])->first();

          if (!$user || !Hash::check($data['password'], $user->password)) {
            return response([
                'msg' => 'incorrect username or password'
            ], 401);
        }

        $token = $user->createToken('apiToken')->plainTextToken;
        $res = [
            'user' => $user,
            'token' => $token
         ];

        return response($res, 201);
    }
    public function logout(Request $request)
    {
          // Revoke the user's current session
    $request->user()->currentAccessToken()->delete();

    // Revoke all tokens (optional)
    // $request->user()->tokens()->delete();

    return response()->json(['message' => 'Logout successful']);

    }
}
