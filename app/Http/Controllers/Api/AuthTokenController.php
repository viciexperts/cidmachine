<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    /**
     * POST /api/login
     * body: { "email": "", "password": "", "abilities": ["assign:phone"] }
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string'],
            'name'      => ['nullable', 'string', 'max:60'], // token name
        ]);

        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }
        $tokenName = $data['name'] ?? 'api-token';

        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token'     => $token,
            'user_id'   => $user->id,
        ], 201);
    }

    /**
     * POST /api/logout
     * Revokes current token.
     */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
