<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Provider;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
            ]);

            if ($user->role === UserRole::Provider) {
                Provider::create([
                    'user_id' => $user->id,
                    'service_types' => $data['service_types'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'vehicle_info' => $data['vehicle_info'] ?? null,
                ]);
            }

            return $user;
        });

        $token = $user->createToken($request->string('name').'-registration')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('provider')),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw new AuthenticationException('These credentials do not match our records.');
        }

        $token = $user->createToken($request->string('device_name'))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('provider')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('provider'));
    }
}
