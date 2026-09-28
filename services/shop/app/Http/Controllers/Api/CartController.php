<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemRequest;
use App\Models\Product;
use App\Modules\Cart\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function add(CartItemRequest $request, Product $product, CartService $carts): JsonResponse
    {
        $cart = $carts->add($carts->current($request->user(), $request->session()), $product, max(1, $request->integer('quantity')));

        return response()->json(['data' => $cart]);
    }

    public function update(CartItemRequest $request, Product $product, CartService $carts): JsonResponse
    {
        $cart = $carts->update($carts->current($request->user(), $request->session()), $product, $request->integer('quantity'));

        return response()->json(['data' => $cart]);
    }
}
