<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_media_is_streamed_without_exposing_object_storage(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('products/test.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $response = $this->get('/media/products/test.svg')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('<svg', $response->streamedContent());
    }

    public function test_media_route_rejects_paths_outside_products(): void
    {
        Storage::fake('s3');

        $this->get('/media/private/test.txt')->assertNotFound();
    }
}
