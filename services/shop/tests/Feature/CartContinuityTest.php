<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_is_attached_after_login(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->post(route('cart.add', $product), ['quantity' => 2, 'intent' => 'cart'])->assertRedirect();
        $guestCart = Cart::query()->whereNull('user_id')->firstOrFail();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertDatabaseHas('carts', ['id' => $guestCart->id, 'user_id' => $user->id, 'session_id' => null, 'status' => 'active']);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $guestCart->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_buy_now_adds_item_then_redirects_to_checkout(): void
    {
        $product = $this->product();

        $this->post(route('cart.add', $product), ['quantity' => 1, 'intent' => 'checkout'])
            ->assertRedirect(route('checkout.show'));

        $cart = Cart::query()->firstOrFail();
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_guest_cart_merges_into_an_existing_customer_cart(): void
    {
        $product = $this->product();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $userCart = Cart::query()->create(['user_id' => $user->id, 'status' => 'active']);
        $userCart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->post(route('cart.add', $product), ['quantity' => 2, 'intent' => 'cart'])->assertRedirect();
        $guestCart = Cart::query()->whereNull('user_id')->firstOrFail();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertDatabaseHas('cart_items', ['cart_id' => $userCart->id, 'product_id' => $product->id, 'quantity' => 3]);
        $this->assertDatabaseHas('carts', ['id' => $guestCart->id, 'status' => 'abandoned']);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $guestCart->id]);
    }

    private function product(): Product
    {
        $category = Category::query()->create(['name' => 'Đồ dùng', 'slug' => 'do-dung', 'status' => 'active']);

        return Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'CART-TEST',
            'slug' => 'san-pham-gio-hang',
            'name' => 'Sản phẩm giỏ hàng',
            'description' => 'Sản phẩm dùng để kiểm tra tính liên tục của giỏ hàng.',
            'price' => 120000,
            'stock' => 10,
            'weight_grams' => 500,
            'status' => 'active',
        ]);
    }
}
