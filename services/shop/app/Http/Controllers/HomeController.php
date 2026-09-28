<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'categories' => Category::query()->where('status', 'active')->withCount('products')->get(),
            'products' => Product::query()->active()->where('featured', true)->with('category')->limit(8)->get(),
        ]);
    }
}
