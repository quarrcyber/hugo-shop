<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_complete_card_checkout_without_shop_storing_pan_or_cvv(): void
    {
        Mail::fake();
        Http::fake([
            'http://mock-shipping:8082/quote' => Http::response(['service' => 'express', 'fee' => 25000, 'estimated_days' => 2]),
            'http://payment-svc:8081/tokenize' => Http::response(['token' => 'tok_ok_test', 'card_type' => 'visa', 'last_four' => '4242', 'expiration' => '12/30'], 201),
            'http://payment-svc:8081/charge' => Http::response(['transaction_id' => 'txn_1234567890abcdefabcd', 'status' => 'succeeded', 'amount' => 224000, 'currency' => 'VND']),
        ]);

        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Bàn làm việc', 'slug' => 'ban-lam-viec', 'status' => 'active']);
        $product = Product::query()->create([
            'category_id' => $category->id, 'sku' => 'TEST-1', 'slug' => 'so-tay-test', 'name' => 'Sổ tay test',
            'description' => 'Mô tả sản phẩm test đủ dài.', 'price' => 199000, 'stock' => 5, 'weight_grams' => 500, 'status' => 'active',
        ]);
        $cart = Cart::query()->create(['user_id' => $user->id, 'status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'recipient_name' => 'Khách Test', 'phone' => '0900000001', 'line1' => '12 Đường Test',
            'ward' => 'Phường 1', 'district' => 'Quận 3', 'city' => 'TP. Hồ Chí Minh',
            'payment_method' => 'card', 'card_number' => '4242424242424242', 'expiration' => '12/30', 'cvv' => '123',
            'save_card' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'paid', 'total' => 224000]);
        $this->assertDatabaseHas('credit_cards', ['user_id' => $user->id, 'last_four' => '4242']);
        $this->assertDatabaseMissing('credit_cards', ['card_token' => '4242424242424242']);
        $this->assertSame(4, $product->fresh()->stock);
        Mail::assertQueued(OrderConfirmation::class);
    }

    public function test_checkout_rolls_back_stock_when_gateway_fails(): void
    {
        Http::fake([
            'http://mock-shipping:8082/quote' => Http::response(['service' => 'express', 'fee' => 25000]),
            'http://payment-svc:8081/tokenize' => Http::response(['token' => 'tok_decline_test', 'card_type' => 'visa', 'last_four' => '0002', 'expiration' => '12/30'], 201),
            'http://payment-svc:8081/charge' => Http::response(['message' => 'declined'], 402),
        ]);
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Nhà cửa', 'slug' => 'nha-cua', 'status' => 'active']);
        $product = Product::query()->create(['category_id' => $category->id, 'sku' => 'TEST-2', 'slug' => 'coc-test', 'name' => 'Cốc test', 'description' => 'Sản phẩm test.', 'price' => 100000, 'stock' => 3, 'weight_grams' => 300, 'status' => 'active']);
        $cart = Cart::query()->create(['user_id' => $user->id, 'status' => 'active']);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->from(route('checkout.show'))->post(route('checkout.store'), [
            'recipient_name' => 'Khách Test', 'phone' => '0900000001', 'line1' => '12 Đường Test', 'ward' => 'Phường 1',
            'district' => 'Quận 3', 'city' => 'TP. Hồ Chí Minh', 'payment_method' => 'card',
            'card_number' => '4000000000000002', 'expiration' => '12/30', 'cvv' => '123',
        ])->assertRedirect(route('checkout.show'))->assertSessionHasErrors('payment');

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'status' => 'cancelled']);
    }
}
