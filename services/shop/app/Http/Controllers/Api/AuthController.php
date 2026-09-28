<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'phone', 'password']) + ['role' => 'customer', 'status' => 'active']);
        event(new Registered($user));

        return response()->json(['token' => $user->createToken('api', ['shop'])->plainTextToken, 'user' => $user], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', (string) $request->string('email'))->first();
        if (! $user || $user->status !== 'active' || ! Hash::check((string) $request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không hợp lệ.']);
        }

        return response()->json(['token' => $user->createToken('api', ['shop'])->plainTextToken, 'user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }
}
