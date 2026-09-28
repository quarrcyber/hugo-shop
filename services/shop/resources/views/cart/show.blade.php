@extends('layouts.app')

@section('title', 'Giỏ hàng · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head"><h1 class="page-title">Giỏ hàng</h1><p class="page-lede">Kiểm tra số lượng trước khi chuyển sang thanh toán.</p></header>
    @if($cart->items->isEmpty())
        <div class="alert"><p>Giỏ hàng chưa có sản phẩm.</p><a href="{{ route('catalog.index') }}">Tiếp tục xem catalog →</a></div>
    @else
        <div class="cart-layout">
            <div class="cart-list">
                @foreach($cart->items as $item)
                    <article class="cart-line">
                        <!-- TODO: Replace with a real product image, target size: 400x500 -->
                        <img src="{{ $item->product->image_path }}" alt="{{ $item->product->name }}" width="400" height="500">
                        <div class="cart-line__meta">
                            <h2 style="font-family: var(--font-body); font-size: var(--text-md)"><a href="{{ route('catalog.show', $item->product) }}">{{ $item->product->name }}</a></h2>
                            <p class="money">{{ number_format($item->product->price, 0, ',', '.') }} ₫</p>
                            <div class="cart-line__actions">
                                <form class="cluster" method="post" action="{{ route('cart.update', $item->product) }}">
                                    @csrf @method('patch')
                                    <label class="field"><span class="field__label">Số lượng</span><input class="field__control quantity" type="number" name="quantity" min="0" max="{{ min(20, $item->product->stock) }}" value="{{ $item->quantity }}"></label>
                                    <button class="btn btn--quiet" type="submit">Cập nhật</button>
                                </form>
                                <form method="post" action="{{ route('cart.remove', $item->product) }}">@csrf @method('delete')<button class="btn btn--danger" type="submit">Xóa</button></form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <aside class="summary" aria-label="Tóm tắt giỏ hàng">
                <div class="summary__row"><span>Tạm tính</span><span class="money">{{ number_format($cart->total, 0, ',', '.') }} ₫</span></div>
                <div class="summary__row"><span>Phí vận chuyển</span><span>Tính ở bước sau</span></div>
                <div class="summary__row summary__total"><span>Tổng tạm tính</span><span class="money">{{ number_format($cart->total, 0, ',', '.') }} ₫</span></div>
                @auth
                    <a class="btn" href="{{ route('checkout.show') }}">Tiếp tục thanh toán</a>
                @else
                    <a class="btn" href="{{ route('login') }}">Đăng nhập để thanh toán</a>
                @endauth
            </aside>
        </div>
    @endif
</div>
@endsection
