@extends('layouts.app')

@section('title', $product->name.' · Hugo Shop')
@section('meta_description', Str::limit($product->description, 150))

@section('content')
<div class="page shell">
    <div class="product-detail">
        <figure class="product-detail__media">
            <!-- TODO: Replace with a real product image, target size: 1200x1200 -->
            <img src="{{ $product->image_path }}" alt="{{ $product->name }} được chụp chính diện trên nền trung tính" width="1200" height="1200">
        </figure>
        <section class="product-detail__info">
            <p class="muted">{{ $product->category->name }} · {{ $product->sku }}</p>
            <h1 class="product-detail__title">{{ $product->name }}</h1>
            <p class="product-detail__price money">{{ number_format($product->price, 0, ',', '.') }} ₫</p>
            <p>{{ $product->description }}</p>
            <p class="rating" aria-label="{{ $product->rating_average }} trên 5 sao">{{ str_repeat('★', (int) round($product->rating_average)) }}{{ str_repeat('☆', 5 - (int) round($product->rating_average)) }} <span class="muted">({{ $product->reviews_count }})</span></p>
            <p class="stock {{ $product->stock === 0 ? 'stock--out' : '' }}">{{ $product->stock > 0 ? 'Còn '.$product->stock.' sản phẩm' : 'Tạm hết hàng' }}</p>
            <form class="form-grid" method="post" action="{{ route('cart.add', $product) }}" x-data="submitState" @submit="submit">
                @csrf
                <label class="field"><span class="field__label">Số lượng</span><input class="field__control quantity" type="number" name="quantity" min="1" max="{{ min(20, $product->stock) }}" value="1"></label>
                <div class="cluster">
                    <button class="btn" type="submit" name="intent" value="cart" :data-state="state" @disabled($product->stock === 0)>Thêm vào giỏ</button>
                    <button class="btn btn--secondary" type="submit" name="intent" value="checkout" :data-state="state" @disabled($product->stock === 0)>Mua ngay</button>
                </div>
            </form>
            @auth
                <form method="post" action="{{ route('account.wishlist', $product) }}">@csrf<button class="btn btn--quiet" type="submit">Lưu vào yêu thích</button></form>
            @endauth
        </section>
    </div>

    <section class="section" aria-labelledby="reviews-title">
        <div class="section-head"><h2 id="reviews-title" class="section-title">Đánh giá đã duyệt</h2><p class="muted">Chỉ người mua từ đơn hoàn tất mới được gửi đánh giá.</p></div>
        <div class="stack">
            @forelse($product->reviews as $review)
                <article class="alert stack">
                    <p class="rating">{{ str_repeat('★', $review->rating) }}</p>
                    <p>{{ $review->content }}</p>
                    <p class="muted">{{ $review->user->name }} · {{ $review->published_at?->format('d/m/Y') }}</p>
                    @can('update', $review)
                        <details class="review-editor">
                            <summary>Sửa đánh giá</summary>
                            <form class="form-grid" method="post" action="{{ route('reviews.update', $review) }}">
                                @csrf
                                @method('patch')
                                <label class="field"><span class="field__label">Số sao</span><select class="field__control" name="rating">@foreach(range(5,1) as $rating)<option value="{{ $rating }}" @selected($review->rating === $rating)>{{ $rating }} sao</option>@endforeach</select></label>
                                <label class="field"><span class="field__label">Nhận xét</span><textarea class="field__control" name="content" minlength="10" maxlength="1500" required>{{ $review->content }}</textarea></label>
                                <button class="btn btn--quiet" type="submit">Gửi duyệt lại</button>
                            </form>
                        </details>
                    @endcan
                    @can('delete', $review)
                        <form method="post" action="{{ route('reviews.destroy', $review) }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn--danger" type="submit">Xóa đánh giá</button>
                        </form>
                    @endcan
                </article>
            @empty
                <p class="muted">Chưa có đánh giá đã duyệt cho sản phẩm này.</p>
            @endforelse
        </div>
        @auth
            <form class="form-grid" method="post" action="{{ route('reviews.store', $product) }}" style="margin-block-start: var(--space-xl)">
                @csrf
                <label class="field"><span class="field__label">Số sao</span><select class="field__control" name="rating">@foreach(range(5,1) as $rating)<option value="{{ $rating }}">{{ $rating }} sao</option>@endforeach</select></label>
                <label class="field"><span class="field__label">Nhận xét</span><textarea class="field__control" name="content" minlength="10" maxlength="1500" placeholder="Điều gì hữu ích với bạn?"></textarea><span class="field__help">Đánh giá sẽ xuất hiện sau khi nhân viên duyệt.</span></label>
                <button class="btn" type="submit">Gửi đánh giá</button>
            </form>
        @endauth
    </section>

    @if($related->isNotEmpty())
        <section class="section"><div class="section-head"><h2 class="section-title">Cùng danh mục</h2></div><div class="product-grid">@foreach($related as $item)<x-product-card :product="$item" />@endforeach</div></section>
    @endif
</div>
@endsection
