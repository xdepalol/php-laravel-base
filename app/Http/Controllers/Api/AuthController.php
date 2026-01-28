<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
   /**
     * Authenticates a user and get token
     *
     * @return AnonymousResourceCollection
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        // Opcional: revocar tokens antics del mateix "device"
        // $user->tokens()->delete();
        $userAgent = $request->userAgent() ?? 'postman';

        $token = $user->createToken($userAgent)->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);    
    }
   
    /**
     * Logouts and removes the session token
     */
    public function logout(Request $request)
    {
        // Revoca només el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out'], 200);
    }
}