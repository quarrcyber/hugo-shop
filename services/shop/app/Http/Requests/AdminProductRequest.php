<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-catalog') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'alpha_dash', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1000'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_grams' => ['required', 'integer', 'between:1,50000'],
            'featured' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,min_height=600'],
        ];
    }
}
