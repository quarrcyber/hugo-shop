<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'sku', 'slug', 'name', 'description', 'price', 'compare_at_price',
        'stock', 'weight_grams', 'image_path', 'featured', 'status', 'rating_average', 'reviews_count',
    ];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'rating_average' => 'decimal:2'];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'published');
    }

    /**
     * @param Builder<Product> $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
