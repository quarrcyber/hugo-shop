<?php

namespace App\Modules\Shipping;

use Illuminate\Support\Facades\Http;

class ShippingClient
{
    /** @return array{service: string, fee: int, estimated_days: int} */
    public function quote(string $district, int $weight): array
    {
        return Http::baseUrl((string) config('services.shipping.url'))
            ->withToken((string) config('services.shipping.key'))
            ->acceptJson()
            ->timeout(4)
            ->retry(2, 100)
            ->post('/quote', ['district' => $district, 'weight_grams' => $weight])
            ->throw()
            ->json();
    }
}
