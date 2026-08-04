<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->where('status', 'active')->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['code' => 'AUTH_INVALID_CREDENTIALS', 'message' => 'Kredensial tidak valid.'], 422);
        }

        return response()->json([
            'data' => ['token' => $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken, 'user' => $user],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }
}
