<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'slug' => ['required', 'string', 'max:140', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'hidden'])],
        ]);
        Category::query()->create($data);

        return back()->with('success', 'Danh mục đã được tạo.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->merge(['slug' => Str::slug((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'slug' => ['required', 'string', 'max:140', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'hidden'])],
        ]);
        $category->update($data);

        return back()->with('success', 'Danh mục đã được cập nhật.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_if($category->products()->exists(), 422, 'Không thể xóa danh mục đang có sản phẩm.');
        $category->delete();

        return back()->with('success', 'Danh mục đã được xóa.');
    }
}
