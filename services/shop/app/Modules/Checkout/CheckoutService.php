<?php

namespace App\Modules\Checkout;

use App\Mail\OrderConfirmation;
use App\Models\Cart;
use App\Models\CreditCard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Modules\Payments\PaymentClient;
use App\Modules\Shipping\ShippingClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutService
{
    public function __construct(private PaymentClient $payments, private ShippingClient $shipping) {}

    /**
     * @param array{
     *     recipient_name: string,
     *     phone: string,
     *     line1: string,
     *     ward: string,
     *     district: string,
     *     city: string,
     *     payment_method: string,
     *     credit_card_id?: int|null,
     *     card_number?: string,
     *     expiration?: string,
     *     cvv?: string,
     *     save_card?: bool,
     *     notes?: string|null
     * } $data
     */
    public function place(User $user, Cart $cart, array $data): Order
    {
        $cart->load('items.product');
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        $method = $data['payment_method'];
        $tokenized = null;
        if ($method === 'card') {
            if (! empty($data['credit_card_id'])) {
                $card = $user->cards()->whereKey($data['credit_card_id'])->firstOrFail();
                $tokenized = [
                    'token' => $card->card_token,
                    'card_type' => $card->card_type,
                    'last_four' => $card->last_four,
                    'expiration' => $card->expiration,
                ];
            } else {
                $tokenized = $this->payments->tokenize($data['card_number'], $data['expiration'], $data['cvv']);
            }
        }

        $weight = (int) $cart->items->sum(fn ($item) => $item->quantity * $item->product->weight_grams);
        $quote = $this->shipping->quote($data['district'], $weight);
        $idempotency = (string) Str::uuid();

        /** @var array{Order, Payment} $created */
        $created = DB::transaction(function () use ($user, $cart, $data, $quote, $method, $idempotency): array {
            $locked = Product::query()->whereIn('id', $cart->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');

            foreach ($cart->items as $item) {
                $product = $locked->get($item->product_id);
                if (! $product || $product->stock < $item->quantity) {
                    throw ValidationException::withMessages(['cart' => "{$item->product->name} không còn đủ hàng."]);
                }
            }

            $subtotal = (int) $cart->items->sum(fn ($item) => $item->quantity * $locked->get($item->product_id)->price);
            $order = Order::query()->create([
                'user_id' => $user->id,
                'order_number' => 'HS-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => (int) $quote['fee'],
                'discount' => 0,
                'total' => $subtotal + (int) $quote['fee'],
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['phone'],
                'shipping_line1' => $data['line1'],
                'shipping_ward' => $data['ward'],
                'shipping_district' => $data['district'],
                'shipping_city' => $data['city'],
                'shipping_service' => $quote['service'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $product = $locked->get($item->product_id);
                $order->items()->create([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item->quantity,
                    'line_total' => $product->price * $item->quantity,
                ]);
                $product->decrement('stock', $item->quantity);
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => $method,
                'status' => $method === 'cod' ? 'succeeded' : 'pending',
                'idempotency_key' => $idempotency,
                'amount' => $order->total,
                'currency' => 'VND',
            ]);
            $cart->update(['status' => 'converted']);

            return [$order, $payment];
        });
        [$order, $payment] = $created;

        if ($method === 'cod') {
            $order->update(['status' => 'processing']);
            Mail::to($user)->queue(new OrderConfirmation($order->load('items')));

            return $order->fresh('items');
        }

        try {
            $result = $this->payments->charge($tokenized['token'], $order->total, $idempotency);
        } catch (Throwable $exception) {
            $this->compensate($order, $payment, ['message' => $exception->getMessage()]);
            $cart->update(['status' => 'active']);
            throw ValidationException::withMessages(['payment' => 'Thanh toán không thành công. Tồn kho đã được hoàn lại.']);
        }

        $payment->update([
            'status' => $result['status'],
            'transaction_id' => $result['transaction_id'],
            'gateway_response' => $result,
        ]);

        if (($data['save_card'] ?? false) && $tokenized) {
            CreditCard::query()->updateOrCreate(['card_token' => $tokenized['token']], [
                'user_id' => $user->id,
                'card_type' => $tokenized['card_type'],
                'last_four' => $tokenized['last_four'],
                'expiration' => $tokenized['expiration'],
                'is_default' => ! $user->cards()->exists(),
            ]);
        }

        if ($result['status'] === 'succeeded') {
            $order->update(['status' => 'paid']);
            Mail::to($user)->queue(new OrderConfirmation($order->load('items')));
        } elseif ($result['status'] === 'requires_action') {
            $order->update(['status' => 'pending']);
        } else {
            $this->compensate($order, $payment, $result);
            $cart->update(['status' => 'active']);
        }

        return $order->fresh('items');
    }

    /** @param array<string, mixed> $response */
    private function compensate(Order $order, Payment $payment, array $response): void
    {
        DB::transaction(function () use ($order, $payment, $response): void {
            $order->load('items');
            foreach ($order->items as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
            $payment->update(['status' => 'failed', 'gateway_response' => $response]);
            $order->update(['status' => 'cancelled']);
        });
    }
}
