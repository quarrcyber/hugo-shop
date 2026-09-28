<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Modules\Cart\CartService;
use App\Modules\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->orders()->with('items')->latest()->paginate(15)]);
    }

    public function store(CheckoutRequest $request, CartService $carts, CheckoutService $checkout): JsonResponse
    {
        $order = $checkout->place($request->user(), $carts->current($request->user(), $request->session()), $request->validated());

        return response()->json(['data' => $order], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(['data' => $order->load(['items', 'payments'])]);
    }
}
