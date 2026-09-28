<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartItemRequest;
use App\Models\Product;
use App\Modules\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Request $request, CartService $carts): View
    {
        return view('cart.show', ['cart' => $carts->current($request->user(), $request->session())]);
    }

    public function add(CartItemRequest $request, Product $product, CartService $carts): RedirectResponse
    {
        $cart = $carts->current($request->user(), $request->session());
        $carts->add($cart, $product, max(1, $request->integer('quantity')));

        if ($request->string('intent')->toString() === 'checkout') {
            return redirect()->route('checkout.show');
        }

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ.');
    }

    public function update(CartItemRequest $request, Product $product, CartService $carts): RedirectResponse
    {
        $carts->update($carts->current($request->user(), $request->session()), $product, $request->integer('quantity'));

        return back()->with('success', 'Giỏ hàng đã được cập nhật.');
    }

    public function remove(Request $request, Product $product, CartService $carts): RedirectResponse
    {
        $carts->update($carts->current($request->user(), $request->session()), $product, 0);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }
}
