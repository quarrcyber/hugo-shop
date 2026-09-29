<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $users = collect([
                ['name' => 'Khách Hàng Mẫu', 'email' => 'customer@hugo-shop.local', 'role' => 'customer'],
                ['name' => 'Nhân Viên Mẫu', 'email' => 'staff@hugo-shop.local', 'role' => 'staff'],
                ['name' => 'Quản Trị Viên', 'email' => 'admin@hugo-shop.local', 'role' => 'admin'],
            ])->map(fn (array $data) => User::query()->create($data + [
                'phone' => '090000000'.random_int(1, 9),
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => 'Password123!',
            ]));

            User::factory(17)->create(['role' => 'customer']);

            $categories = collect([
                ['name' => 'Đồ dùng bàn làm việc', 'description' => 'Những vật dụng gọn gàng cho một góc làm việc dễ chịu.'],
                ['name' => 'Nhà cửa', 'description' => 'Đồ dùng thiết thực cho nhịp sống hằng ngày.'],
                ['name' => 'Phụ kiện cá nhân', 'description' => 'Những món nhỏ được chọn theo độ bền và tính tiện dụng.'],
                ['name' => 'Công nghệ', 'description' => 'Phụ kiện công nghệ tối giản, dễ kết hợp.'],
                ['name' => 'Quà tặng', 'description' => 'Các lựa chọn quà tặng đóng gói sẵn.'],
            ])->map(fn (array $category) => Category::query()->create($category + [
                'slug' => Str::slug($category['name']),
                'status' => 'active',
                'image_path' => '/images/placeholders/category.svg',
            ]));

            $adjectives = ['Tĩnh', 'Mộc', 'Lam', 'Sớm', 'Nâu', 'Rêu', 'Gọn', 'An', 'Thô', 'Nhẹ'];
            $nouns = ['Sổ tay', 'Đèn bàn', 'Bình nước', 'Khay gỗ', 'Túi vải', 'Cốc sứ', 'Kệ nhỏ', 'Ví gập', 'Cáp sạc', 'Hộp quà'];

            $products = collect();

            foreach (range(1, 50) as $index) {
                $name = $nouns[($index - 1) % count($nouns)].' '.$adjectives[($index - 1) % count($adjectives)].' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
                $products->push(Product::query()->create([
                    'category_id' => $categories[($index - 1) % $categories->count()]->id,
                    'sku' => 'HUGO-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                    'slug' => Str::slug($name).'-'.$index,
                    'name' => $name,
                    'description' => 'Sản phẩm mẫu phục vụ môi trường học tập. Thông tin, tồn kho và hình ảnh đều là dữ liệu giả.',
                    'price' => 79000 + ($index * 17000),
                    'compare_at_price' => $index % 4 === 0 ? 99000 + ($index * 18000) : null,
                    'stock' => 8 + ($index % 29),
                    'weight_grams' => 250 + ($index * 35),
                    'image_path' => '/images/placeholders/product-'.(($index - 1) % 6 + 1).'.svg',
                    'featured' => $index <= 8,
                    'status' => 'active',
                ]));
            }

            $customer = $users->firstWhere('role', 'customer');
            Address::query()->create([
                'user_id' => $customer->id,
                'label' => 'Nhà riêng',
                'recipient_name' => $customer->name,
                'phone' => '0900000001',
                'line1' => '12 Đường Học Tập',
                'ward' => 'Phường 1',
                'district' => 'Quận 3',
                'city' => 'TP. Hồ Chí Minh',
                'is_default' => true,
            ]);

            CreditCard::query()->create([
                'user_id' => $customer->id,
                'card_type' => 'visa',
                'last_four' => '4242',
                'card_token' => 'tok_seed_'.Str::random(32),
                'expiration' => '12/30',
                'is_default' => true,
            ]);

            foreach (range(1, 6) as $number) {
                $orderProducts = $products->slice(($number - 1) * 2, 2);
                $subtotal = (int) $orderProducts->sum('price');
                $order = Order::query()->create([
                    'user_id' => $customer->id,
                    'order_number' => 'HS-'.now()->format('ymd').'-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
                    'status' => $number <= 4 ? 'completed' : 'processing',
                    'subtotal' => $subtotal,
                    'shipping_fee' => 25000,
                    'discount' => 0,
                    'total' => $subtotal + 25000,
                    'recipient_name' => $customer->name,
                    'recipient_phone' => '0900000001',
                    'shipping_line1' => '12 Đường Học Tập',
                    'shipping_ward' => 'Phường 1',
                    'shipping_district' => 'Quận 3',
                    'shipping_city' => 'TP. Hồ Chí Minh',
                    'shipping_service' => 'express',
                ]);

                foreach ($orderProducts as $product) {
                    $order->items()->create([
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'unit_price' => $product->price,
                        'quantity' => 1,
                        'line_total' => $product->price,
                    ]);
                }

                if ($number <= 4) {
                    $reviewed = $orderProducts->first();
                    Review::query()->create([
                        'user_id' => $customer->id,
                        'product_id' => $reviewed->id,
                        'order_id' => $order->id,
                        'rating' => 4 + ($number % 2),
                        'content' => 'Đánh giá mẫu cho luồng kiểm thử. Sản phẩm đúng mô tả và đóng gói gọn.',
                        'status' => 'published',
                        'published_at' => now()->subDays($number),
                    ]);
                }
            }

            Product::query()->each(function (Product $product): void {
                $published = $product->reviews()->get();
                $product->update([
                    'rating_average' => $published->avg('rating') ?: 0,
                    'reviews_count' => $published->count(),
                ]);
            });
        });
    }
}
