<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', ['users' => User::query()->latest()->paginate(25)]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Không thể thay đổi chính tài khoản đang đăng nhập.');
        $data = $request->validate([
            'role' => ['required', Rule::in(['customer', 'staff', 'admin'])],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);
        $user->update($data);

        return back()->with('success', 'Tài khoản đã được cập nhật.');
    }
}
