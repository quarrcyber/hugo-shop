@extends('layouts.app')

@section('title', 'Sản phẩm · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head">
        <h1 class="page-title">Catalog</h1>
    </header>

    <form class="filters" method="get" action="{{ route('catalog.index') }}">
        <div class="filter-row">
            <label class="field"><span class="field__label">Tìm kiếm</span><input class="field__control" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Sổ tay, đèn bàn…"></label>
            <label class="field"><span class="field__label">Danh mục</span><select class="field__control" name="category"><option value="">Tất cả</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="field"><span class="field__label">Giá từ</span><input class="field__control" type="number" min="0" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="0"></label>
            <label class="field"><span class="field__label">Đến</span><input class="field__control" type="number" min="0" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="2.000.000"></label>
            <label class="field"><span class="field__label">Sắp xếp</span><select class="field__control" name="sort"><option value="newest">Mới nhất</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Giá tăng dần</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Giá giảm dần</option><option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Đánh giá tốt</option></select></label>
        </div>
        <div class="cluster"><button class="btn" type="submit">Áp dụng</button><a class="btn btn--quiet" href="{{ route('catalog.index') }}">Đặt lại</a></div>
    </form>

    <p class="muted" style="margin-block-end: var(--space-lg)">{{ $products->total() }} sản phẩm phù hợp</p>
    <div class="product-grid product-grid--dense">
        @forelse($products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="alert">Không tìm thấy sản phẩm. Hãy thử bỏ bớt bộ lọc.</div>
        @endforelse
    </div>
    <div class="pagination">{{ $products->links() }}</div>
</div>
@endsection
