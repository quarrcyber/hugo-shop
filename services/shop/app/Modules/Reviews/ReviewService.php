<?php

namespace App\Modules\Reviews;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    /** @param array{rating: int, content: string} $data */
    public function create(User $user, Product $product, array $data): Review
    {
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->latest()
            ->first();

        if (! $order) {
            throw ValidationException::withMessages(['review' => 'Bạn chỉ có thể đánh giá sản phẩm thuộc đơn đã hoàn tất.']);
        }

        return Review::query()->updateOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
        ], [
            'rating' => $data['rating'],
            'content' => $data['content'],
            'status' => 'pending',
            'published_at' => null,
        ]);
    }

    public function moderate(Review $review, User $moderator, string $status): Review
    {
        $review->update([
            'status' => $status,
            'moderated_by' => $moderator->id,
            'published_at' => $status === 'published' ? now() : null,
        ]);

        $this->refreshProductRating($review->product);

        return $review->fresh();
    }

    /** @param array{rating: int, content: string} $data */
    public function update(Review $review, array $data): Review
    {
        $product = $review->product;
        $review->update($data + ['status' => 'pending', 'published_at' => null]);
        $this->refreshProductRating($product);

        return $review->fresh();
    }

    public function delete(Review $review): void
    {
        $product = $review->product;
        $review->delete();
        $this->refreshProductRating($product);
    }

    private function refreshProductRating(Product $product): void
    {
        $published = $product->reviews()->get();
        $product->update([
            'rating_average' => $published->avg('rating') ?: 0,
            'reviews_count' => $published->count(),
        ]);
    }
}
