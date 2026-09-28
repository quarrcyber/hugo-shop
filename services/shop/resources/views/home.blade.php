@extends('layouts.app')

@section('title', 'Hugo Shop · Đồ dùng chọn lọc cho nhịp sống hằng ngày')
@section('meta_description', 'Khám phá danh mục sản phẩm mẫu tiếng Việt của Hugo Shop trong môi trường thương mại điện tử local.')

@section('content')
<div class="shell">
    <section class="catalog-intro" aria-labelledby="home-title">
        <h1 id="home-title" class="catalog-intro__title">Những món đồ dùng được chọn để sống gọn hơn.</h1>
        <p class="catalog-intro__meta">{{ $products->count() }} sản phẩm nổi bật · {{ $categories->count() }} danh mục · Dữ liệu cập nhật từ kho mẫu</p>
    </section>

    <nav id="danh-muc" class="category-rail" aria-label="Danh mục sản phẩm">
        @foreach($categories as $category)
            <a class="category-rail__link" href="{{ route('catalog.index', ['category' => $category->slug]) }}">{{ $category->name }} · {{ $category->products_count }}</a>
        @endforeach
    </nav>

    <section id="goi-y" class="section" aria-labelledby="featured-title">
        <div class="section-head">
            <div>
                <h2 id="featured-title" class="section-title">Chọn lọc hôm nay</h2>
                <p class="muted">Một lát cắt từ kho sản phẩm mẫu.</p>
            </div>
            <a class="section-link" href="{{ route('catalog.index') }}">Xem toàn bộ →</a>
        </div>
        <div class="product-grid">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="promo-band">
            <div>
                <h2>Thanh toán giả lập, quy trình thật.</h2>
                <p>Mọi đơn hàng, email, tồn kho và trạng thái giao dịch đều chạy qua luồng nghiệp vụ hoàn chỉnh.</p>
            </div>
            <a href="{{ route('catalog.index') }}">Bắt đầu từ catalog →</a>
        </div>
    </section>
</div>
@endsection
