<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Modules\Reviews\ReviewService;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, ReviewService $reviews): JsonResponse
    {
        return response()->json(['data' => $reviews->create($request->user(), $product, $request->validated())], 201);
    }
}
