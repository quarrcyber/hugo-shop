<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Modules\Cart\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request, CartService $carts): RedirectResponse
    {
        $guestCart = $carts->guest($request->session());

        if (! Auth::attempt($request->safe()->only(['email', 'password']), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        if ($request->user()->status !== 'active') {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Tài khoản đã bị tạm khóa.']);
        }

        $request->session()->regenerate();
        if ($guestCart) {
            $carts->attachToUser($guestCart, $request->user());
            $request->session()->forget('cart_id');
        }

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, CartService $carts): RedirectResponse
    {
        $guestCart = $carts->guest($request->session());
        $user = User::query()->create($request->safe()->only(['name', 'email', 'phone', 'password']) + ['role' => 'customer', 'status' => 'active']);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        if ($guestCart) {
            $carts->attachToUser($guestCart, $user);
            $request->session()->forget('cart_id');
        }

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
