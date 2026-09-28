<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        abort_unless(str_starts_with($path, 'products/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('s3')->exists($path), 404);

        return Storage::disk('s3')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
