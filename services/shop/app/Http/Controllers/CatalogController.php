<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductFilterRequest;
use App\Models\Category;
use App\Models\Product;
use App\Modules\Catalog\ProductQuery;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(ProductFilterRequest $request, ProductQuery $products): View
    {
        return view('catalog.index', [
            'products' => $products->paginate($request->validated()),
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'filters' => $request->validated(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'active', 404);

        return view('catalog.show', [
            'product' => $product->load(['category', 'reviews.user']),
            'related' => Product::query()->active()->where('category_id', $product->category_id)->whereKeyNot($product->id)->limit(4)->get(),
        ]);
    }
}
