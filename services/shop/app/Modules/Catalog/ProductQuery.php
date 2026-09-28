<?php

namespace App\Modules\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductQuery
{
    /**
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()->active()->with('category');

        $query->when($filters['q'] ?? null, fn ($builder, string $term) => $builder
            ->where(fn ($inner) => $inner->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")));
        $query->when($filters['category'] ?? null, fn ($builder, string $slug) => $builder
            ->whereHas('category', fn ($category) => $category->where('slug', $slug)));
        $query->when($filters['min_price'] ?? null, fn ($builder, mixed $price) => $builder->where('price', '>=', (int) $price));
        $query->when($filters['max_price'] ?? null, fn ($builder, mixed $price) => $builder->where('price', '<=', (int) $price));

        match ($filters['sort'] ?? 'newest') {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating_average'),
            default => $query->latest(),
        };

        return $query->paginate($perPage)->withQueryString();
    }
}
