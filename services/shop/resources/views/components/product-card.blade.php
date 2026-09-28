@props(['product'])
<article class="product-card">
    <a class="product-card__media" href="{{ route('catalog.show', $product) }}">
        <!-- TODO: Replace with a real product image, target size: 900x1200 -->
        <img src="{{ $product->image_path }}" alt="{{ $product->name }} được chụp trên nền giấy trung tính" width="900" height="1200" loading="lazy">
    </a>
    <div class="product-card__meta">
        <div>
            <h3 class="product-card__name"><a href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a></h3>
            <p class="product-card__category">{{ $product->category?->name }}</p>
        </div>
        <p class="product-card__price money">{{ number_format($product->price, 0, ',', '.') }} ₫</p>
    </div>
    <form method="post" action="{{ route('cart.add', $product) }}" x-data="submitState" @submit="submit">
        @csrf
        <input type="hidden" name="quantity" value="1">
        <button class="product-card__add" type="submit" :data-state="state" aria-label="Thêm {{ $product->name }} vào giỏ" @disabled($product->stock === 0)>+</button>
    </form>
</article>
