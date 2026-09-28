@extends('layouts.app')

@section('title', 'Quản lý sản phẩm · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head">
        <h1 class="page-title">Sản phẩm và danh mục</h1>
        <p class="page-lede">Ảnh tải lên được kiểm tra định dạng, kích thước rồi lưu vào MinIO.</p>
    </header>
    @include('admin._nav')

    <section class="section">
        <div class="section-head"><h2 class="section-title">Thêm sản phẩm</h2></div>
        <form class="form-grid" method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="filter-row">
                <label class="field"><span class="field__label">Tên</span><input class="field__control" name="name" value="{{ old('name') }}" required></label>
                <label class="field"><span class="field__label">SKU</span><input class="field__control" name="sku" value="{{ old('sku') }}" required></label>
                <label class="field"><span class="field__label">Danh mục</span><select class="field__control" name="category_id">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
                <label class="field"><span class="field__label">Giá</span><input class="field__control" type="number" name="price" min="1000" value="{{ old('price') }}" required></label>
                <label class="field"><span class="field__label">Tồn kho</span><input class="field__control" type="number" name="stock" min="0" value="{{ old('stock', 0) }}" required></label>
                <label class="field"><span class="field__label">Khối lượng gram</span><input class="field__control" type="number" name="weight_grams" min="1" value="{{ old('weight_grams', 500) }}" required></label>
            </div>
            <label class="field"><span class="field__label">Mô tả</span><textarea class="field__control" name="description" required>{{ old('description') }}</textarea></label>
            <label class="field"><span class="field__label">Trạng thái</span><select class="field__control" name="status"><option value="active">Đang bán</option><option value="draft">Bản nháp</option><option value="archived">Lưu trữ</option></select></label>
            <label class="field"><span class="field__label">Ảnh sản phẩm</span><input class="field__control" type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="field__help">JPG, PNG hoặc WebP; tối đa 4 MB; ít nhất 600 × 600.</span></label>
            <label class="cluster"><input type="checkbox" name="featured" value="1"> Sản phẩm nổi bật</label>
            <button class="btn" type="submit">Tạo sản phẩm</button>
        </form>
    </section>

    <section class="section">
        <div class="section-head"><h2 class="section-title">Kho sản phẩm</h2></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>SKU</th><th>Sản phẩm</th><th>Danh mục</th><th>Giá</th><th>Tồn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td class="money">{{ number_format($product->price, 0, ',', '.') }} ₫</td>
                            <td>{{ $product->stock }}</td>
                            <td><span class="badge">{{ $product->status }}</span></td>
                            <td>
                                <details class="admin-editor">
                                    <summary>Sửa</summary>
                                    <form class="form-grid" method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('patch')
                                        <label class="field"><span class="field__label">Tên</span><input class="field__control" name="name" value="{{ $product->name }}" required></label>
                                        <label class="field"><span class="field__label">SKU</span><input class="field__control" name="sku" value="{{ $product->sku }}" required></label>
                                        <label class="field"><span class="field__label">Danh mục</span><select class="field__control" name="category_id">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                                        <label class="field"><span class="field__label">Giá</span><input class="field__control" type="number" name="price" min="1000" value="{{ $product->price }}" required></label>
                                        <label class="field"><span class="field__label">Tồn kho</span><input class="field__control" type="number" name="stock" min="0" value="{{ $product->stock }}" required></label>
                                        <label class="field"><span class="field__label">Khối lượng gram</span><input class="field__control" type="number" name="weight_grams" min="1" value="{{ $product->weight_grams }}" required></label>
                                        <label class="field"><span class="field__label">Mô tả</span><textarea class="field__control" name="description" required>{{ $product->description }}</textarea></label>
                                        <label class="field"><span class="field__label">Trạng thái</span><select class="field__control" name="status"><option value="active" @selected($product->status === 'active')>Đang bán</option><option value="draft" @selected($product->status === 'draft')>Bản nháp</option><option value="archived" @selected($product->status === 'archived')>Lưu trữ</option></select></label>
                                        <label class="field"><span class="field__label">Thay ảnh</span><input class="field__control" type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                                        <label class="cluster"><input type="checkbox" name="featured" value="1" @checked($product->featured)> Sản phẩm nổi bật</label>
                                        <button class="btn" type="submit">Lưu sản phẩm</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.products.destroy', $product) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn--danger" type="submit">Lưu trữ sản phẩm</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $products->links() }}</div>
    </section>

    <section class="section">
        <div class="section-head"><h2 class="section-title">Danh mục</h2></div>
        <form class="filter-row" method="post" action="{{ route('admin.categories.store') }}">
            @csrf
            <label class="field"><span class="field__label">Tên danh mục</span><input class="field__control" name="name" required></label>
            <label class="field"><span class="field__label">Mô tả</span><input class="field__control" name="description"></label>
            <label class="field"><span class="field__label">Trạng thái</span><select class="field__control" name="status"><option value="active">Hiện</option><option value="hidden">Ẩn</option></select></label>
            <button class="btn" type="submit">Thêm danh mục</button>
        </form>

        <div class="table-wrap category-table">
            <table class="data-table">
                <thead><tr><th>Danh mục</th><th>Sản phẩm</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td><span class="badge">{{ $category->status }}</span></td>
                            <td>
                                <details class="admin-editor">
                                    <summary>Sửa</summary>
                                    <form class="form-grid" method="post" action="{{ route('admin.categories.update', $category) }}">
                                        @csrf
                                        @method('patch')
                                        <label class="field"><span class="field__label">Tên</span><input class="field__control" name="name" value="{{ $category->name }}" required></label>
                                        <label class="field"><span class="field__label">Mô tả</span><textarea class="field__control" name="description">{{ $category->description }}</textarea></label>
                                        <label class="field"><span class="field__label">Trạng thái</span><select class="field__control" name="status"><option value="active" @selected($category->status === 'active')>Hiện</option><option value="hidden" @selected($category->status === 'hidden')>Ẩn</option></select></label>
                                        <button class="btn" type="submit">Lưu danh mục</button>
                                    </form>
                                    @if($category->products_count === 0)
                                        <form method="post" action="{{ route('admin.categories.destroy', $category) }}">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn--danger" type="submit">Xóa danh mục</button>
                                        </form>
                                    @endif
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
