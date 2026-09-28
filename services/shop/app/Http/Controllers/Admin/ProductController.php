<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('admin.products', [
            'products' => Product::query()->with('category')->latest()->paginate(20),
            'categories' => Category::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(AdminProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['featured'] = $request->boolean('featured');
        $data['slug'] = $this->uniqueSlug($data['name']);
        if ($request->hasFile('image')) {
            $data['image_path'] = '/media/'.$request->file('image')->store('products', 's3');
        } else {
            $data['image_path'] = '/images/placeholders/product-1.svg';
        }
        Product::query()->create($data);

        return back()->with('success', 'Sản phẩm đã được tạo.');
    }

    public function update(AdminProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['featured'] = $request->boolean('featured');
        $data['slug'] = $product->name === $data['name'] ? $product->slug : $this->uniqueSlug($data['name'], $product->id);
        if ($request->hasFile('image')) {
            $data['image_path'] = '/media/'.$request->file('image')->store('products', 's3');
        }
        $product->update($data);

        return back()->with('success', 'Sản phẩm đã được cập nhật.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('manage-catalog');
        $product->update(['status' => 'archived']);
        $product->delete();

        return back()->with('success', 'Sản phẩm đã được lưu trữ.');
    }

    private function uniqueSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;
        while (Product::query()->withTrashed()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
