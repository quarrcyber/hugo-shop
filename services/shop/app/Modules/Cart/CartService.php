<?php

namespace App\Modules\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function current(?User $user, Session $session): Cart
    {
        if ($user) {
            $cart = Cart::query()
                ->where('status', 'active')
                ->where('user_id', $user->id)
                ->with('items.product')
                ->first();
        } else {
            $cart = $this->guest($session)?->load('items.product');
        }

        $cart ??= Cart::query()->create([
            'user_id' => $user?->id,
            'session_id' => $user ? null : $session->getId(),
            'status' => 'active',
        ])->load('items.product');

        if (! $user) {
            $session->put('cart_id', $cart->id);
        }

        return $cart;
    }

    public function guest(Session $session): ?Cart
    {
        $cartId = $session->get('cart_id');
        if (is_numeric($cartId)) {
            $cart = Cart::query()->whereKey((int) $cartId)->where('status', 'active')->whereNull('user_id')->first();
            if ($cart) {
                return $cart;
            }
        }

        return Cart::query()
            ->where('status', 'active')
            ->whereNull('user_id')
            ->where('session_id', $session->getId())
            ->first();
    }

    public function add(Cart $cart, Product $product, int $quantity): Cart
    {
        if ($product->status !== 'active' || $product->stock < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Số lượng yêu cầu hiện không còn trong kho.']);
        }

        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min($product->stock, $item->exists ? $item->quantity + $quantity : $quantity);
        $item->save();

        return $cart->fresh('items.product');
    }

    public function update(Cart $cart, Product $product, int $quantity): Cart
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages(['quantity' => 'Số lượng yêu cầu vượt quá tồn kho.']);
        }

        if ($quantity === 0) {
            $cart->items()->where('product_id', $product->id)->delete();
        } else {
            $cart->items()->where('product_id', $product->id)->update(['quantity' => $quantity]);
        }

        return $cart->fresh('items.product');
    }

    public function attachToUser(Cart $cart, User $user): void
    {
        $userCart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereKeyNot($cart->id)
            ->with('items')
            ->first();

        if (! $userCart) {
            $cart->update(['user_id' => $user->id, 'session_id' => null]);

            return;
        }

        $cart->load('items');
        foreach ($cart->items as $item) {
            $product = Product::query()->whereKey($item->product_id)->where('status', 'active')->first();
            if (! $product || $product->stock < 1) {
                continue;
            }
            $target = $userCart->items()->firstOrNew(['product_id' => $item->product_id]);
            $target->quantity = min($product->stock, (int) $target->quantity + $item->quantity);
            $target->save();
        }

        $cart->items()->delete();
        $cart->update(['status' => 'abandoned']);
    }
}
