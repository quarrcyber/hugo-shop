<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, (string) config('services.payment.webhook_secret'));
        abort_unless(hash_equals($expected, (string) $request->header('X-Hugo-Signature')), 401);

        $event = $request->json()->all();
        abort_unless(($event['type'] ?? null) === 'payment.updated' && isset($event['data']['transaction_id'], $event['data']['status']), 422);
        abort_unless(in_array($event['data']['status'], ['succeeded', 'failed', 'requires_action'], true), 422);
        $payment = Payment::query()->where('transaction_id', $event['data']['transaction_id'])->first();
        if (! $payment && isset($event['data']['idempotency_key'])) {
            $payment = Payment::query()->where('idempotency_key', $event['data']['idempotency_key'])->first();
        }
        if ($payment && $payment->status !== 'refunded' && ! in_array($payment->order->status, ['cancelled', 'refunded'], true)) {
            $payment->update([
                'status' => $event['data']['status'],
                'transaction_id' => $event['data']['transaction_id'],
                'gateway_response' => $event['data'],
            ]);
            if ($event['data']['status'] === 'succeeded') {
                $payment->order()->update(['status' => 'paid']);
            }
        }

        return response()->json(['received' => true]);
    }
}
