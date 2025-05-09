<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // Fungsi Login
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        // Buat token API untuk user
        $token = $user->createToken('auth_token')->plainTextToken;
        if($user->role === 'teacher'){
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => new UserResource($user->loadMissing('teacher'))
            ]);
        } elseif ($user->role === 'student') {
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => new UserResource($user->loadMissing('student'))
            ]);
        } else{
            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => new UserResource($user)
            ]);
        }
    }

    // Fungsi Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete(); // Hapus semua token user

        return response()->json(['message' => 'Logout berhasil']);
    }

    // Fungsi untuk mendapatkan user yang sedang login
    public function me(Request $request)
    {
        return new UserResource($request->user()->load('teacher', 'student'));
    }
    
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        $personalToken = PersonalAccessToken::findToken($token);

        if (!$personalToken) {
            return response()->json(['message' => 'Your Session Login is Expired!'], 401);
        }
        $user = $personalToken->tokenable;

        return response()->json([
            'status' => 200,
            'message' => 'Token is valid',
            'user' => $user->only('id', 'name', 'email'),
        ], 200);
    }
}
