<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_webhook_matches_pending_payment_by_idempotency_key(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'HS-WEBHOOK-1',
            'status' => 'pending',
            'subtotal' => 100000,
            'shipping_fee' => 25000,
            'discount' => 0,
            'total' => 125000,
            'recipient_name' => 'Webhook Test',
            'recipient_phone' => '0900000001',
            'shipping_line1' => '12 Test',
            'shipping_ward' => 'Phường 1',
            'shipping_district' => 'Quận 3',
            'shipping_city' => 'TP. Hồ Chí Minh',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'method' => 'card',
            'status' => 'pending',
            'idempotency_key' => 'checkout-webhook-test',
            'amount' => 125000,
            'currency' => 'VND',
        ]);
        $payload = json_encode([
            'type' => 'payment.updated',
            'data' => [
                'transaction_id' => 'txn_1234567890abcdefabcd',
                'idempotency_key' => 'checkout-webhook-test',
                'status' => 'succeeded',
                'amount' => 125000,
                'currency' => 'VND',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/v1/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUGO_SIGNATURE' => 'invalid',
        ], $payload)->assertUnauthorized();
        $this->assertSame('pending', $payment->fresh()->status);

        $signature = hash_hmac('sha256', $payload, (string) config('services.payment.webhook_secret'));
        $this->call('POST', '/api/v1/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUGO_SIGNATURE' => $signature,
        ], $payload)->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'transaction_id' => 'txn_1234567890abcdefabcd',
        ]);
        $this->assertSame('paid', $order->fresh()->status);

        $payment->update(['status' => 'refunded']);
        $order->update(['status' => 'refunded']);
        $this->call('POST', '/api/v1/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUGO_SIGNATURE' => $signature,
        ], $payload)->assertOk();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->status);
    }
}
