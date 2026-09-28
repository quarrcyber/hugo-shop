<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json(['data' => [
            'pending_orders' => Order::query()->whereIn('status', ['paid', 'processing'])->count(),
            'low_stock_products' => Product::query()->where('stock', '<=', 5)->count(),
            'pending_reviews' => Review::query()->where('status', 'pending')->count(),
        ]]);
    }

    public function users(): JsonResponse
    {
        return response()->json(['data' => User::query()->latest()->paginate(25)]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->is($user), 422, 'Không thể thay đổi chính tài khoản đang đăng nhập.');
        $data = $request->validate([
            'role' => ['required', Rule::in(['customer', 'staff', 'admin'])],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);
        $user->update($data);

        return response()->json(['data' => $user->fresh()]);
    }
}
