<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_customer_with_completed_order_can_review(): void
    {
        $buyer = User::factory()->create();
        $outsider = User::factory()->create();
        $category = Category::query()->create(['name' => 'Quà tặng', 'slug' => 'qua-tang', 'status' => 'active']);
        $product = Product::query()->create(['category_id' => $category->id, 'sku' => 'REVIEW-1', 'slug' => 'hop-qua', 'name' => 'Hộp quà', 'description' => 'Sản phẩm mẫu.', 'price' => 250000, 'stock' => 3, 'status' => 'active']);
        $order = Order::query()->create([
            'user_id' => $buyer->id, 'order_number' => 'HS-REVIEW-1', 'status' => 'completed', 'subtotal' => 250000,
            'shipping_fee' => 0, 'discount' => 0, 'total' => 250000, 'recipient_name' => 'Buyer', 'recipient_phone' => '0900000001',
            'shipping_line1' => '1 Test', 'shipping_ward' => 'P1', 'shipping_district' => 'Q1', 'shipping_city' => 'HCM',
        ]);
        $order->items()->create(['product_id' => $product->id, 'sku' => $product->sku, 'product_name' => $product->name, 'unit_price' => 250000, 'quantity' => 1, 'line_total' => 250000]);

        $this->actingAs($buyer)->post(route('reviews.store', $product), ['rating' => 5, 'content' => 'Sản phẩm đúng mô tả và đóng gói tốt.'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['user_id' => $buyer->id, 'product_id' => $product->id, 'status' => 'pending']);

        $this->actingAs($outsider)->post(route('reviews.store', $product), ['rating' => 5, 'content' => 'Tôi chưa mua nhưng vẫn đánh giá.'])->assertSessionHasErrors('review');
        $this->assertDatabaseMissing('reviews', ['user_id' => $outsider->id]);
    }

    public function test_review_input_is_validated(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Công nghệ', 'slug' => 'cong-nghe', 'status' => 'active']);
        $product = Product::query()->create(['category_id' => $category->id, 'sku' => 'VALID-1', 'slug' => 'cap-sac', 'name' => 'Cáp sạc', 'description' => 'Sản phẩm mẫu.', 'price' => 90000, 'stock' => 3, 'status' => 'active']);

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 9, 'content' => '<script>x</script>'])->assertSessionHasErrors(['rating']);
    }

    public function test_editing_or_deleting_a_published_review_refreshes_product_rating(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create(['name' => 'Nhà cửa', 'slug' => 'nha-cua-rating', 'status' => 'active']);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'RATING-1',
            'slug' => 'san-pham-rating',
            'name' => 'Sản phẩm rating',
            'description' => 'Sản phẩm dùng để kiểm tra điểm đánh giá.',
            'price' => 100000,
            'stock' => 3,
            'status' => 'active',
            'rating_average' => 5,
            'reviews_count' => 1,
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'HS-RATING-1',
            'status' => 'completed',
            'subtotal' => 100000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100000,
            'recipient_name' => 'Rating Test',
            'recipient_phone' => '0900000001',
            'shipping_line1' => '1 Test',
            'shipping_ward' => 'P1',
            'shipping_district' => 'Q1',
            'shipping_city' => 'HCM',
        ]);
        $review = Review::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'content' => 'Đánh giá đã được xuất bản trước đó.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->actingAs($user)->patch(route('reviews.update', $review), [
            'rating' => 4,
            'content' => 'Nội dung được chỉnh sửa và cần duyệt lại.',
        ])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'pending', 'rating' => 4]);
        $this->assertSame(0, $product->fresh()->reviews_count);

        $review->update(['status' => 'published', 'published_at' => now()]);
        $product->update(['rating_average' => 4, 'reviews_count' => 1]);
        $this->actingAs($user)->delete(route('reviews.destroy', $review))->assertRedirect();
        $this->assertSame(0, $product->fresh()->reviews_count);
    }
}
