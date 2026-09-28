<?php

namespace App\Modules\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PaymentClient
{
    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.payment.url'))
            ->withToken((string) config('services.payment.key'))
            ->acceptJson()
            ->timeout(5)
            ->retry(2, 150);
    }

    /** @return array{token: string, card_type: string, last_four: string, expiration: string} */
    public function tokenize(string $number, string $expiration, string $cvv): array
    {
        return $this->client()->post('/tokenize', [
            'card_number' => $number,
            'expiration' => $expiration,
            'cvv' => $cvv,
        ])->throw()->json();
    }

    /** @return array{status: string, transaction_id: string, amount?: int, currency?: string, idempotency_key?: string, message?: string} */
    public function charge(string $token, int $amount, string $idempotencyKey): array
    {
        return $this->client()->withHeader('Idempotency-Key', $idempotencyKey)->post('/charge', [
            'token' => $token,
            'amount' => $amount,
            'currency' => 'VND',
        ])->throw()->json();
    }

    /** @return array{status: string, transaction_id: string, refund_id: string} */
    public function refund(string $transactionId): array
    {
        return $this->client()->post('/refund', ['transaction_id' => $transactionId])->throw()->json();
    }
}
