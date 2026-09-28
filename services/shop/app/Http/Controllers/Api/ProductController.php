<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductFilterRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Modules\Catalog\ProductQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request, ProductQuery $products): AnonymousResourceCollection
    {
        return ProductResource::collection($products->paginate($request->validated()));
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->status === 'active', 404);

        return new ProductResource($product->load('category'));
    }
}
