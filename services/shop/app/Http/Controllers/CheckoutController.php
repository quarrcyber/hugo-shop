<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Modules\Cart\CartService;
use App\Modules\Checkout\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $carts): View
    {
        return view('checkout.show', [
            'cart' => $carts->current($request->user(), $request->session()),
            'address' => $request->user()->addresses()->where('is_default', true)->first(),
            'cards' => $request->user()->cards()->latest()->get(),
        ]);
    }

    public function store(CheckoutRequest $request, CartService $carts, CheckoutService $checkout): RedirectResponse
    {
        $order = $checkout->place($request->user(), $carts->current($request->user(), $request->session()), $request->validated());

        return redirect()->route('checkout.success', $order)->with('success', 'Đơn hàng đã được tiếp nhận.');
    }

    public function success(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        return view('checkout.success', ['order' => $order->load('items')]);
    }
}
