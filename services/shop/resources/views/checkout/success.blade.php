@extends('layouts.app')

@section('title', 'Đơn hàng '.$order->order_number.' · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head"><h1 class="page-title">Đơn hàng đã được ghi nhận.</h1><p class="page-lede">Mã đơn {{ $order->order_number }} · Trạng thái {{ $order->status }}</p></header>
    <div class="checkout-layout">
        <div class="stack">
            @foreach($order->items as $item)<div class="summary__row"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span class="money">{{ number_format($item->line_total, 0, ',', '.') }} ₫</span></div>@endforeach
        </div>
        <aside class="summary"><div class="summary__row"><span>Tạm tính</span><span class="money">{{ number_format($order->subtotal, 0, ',', '.') }} ₫</span></div><div class="summary__row"><span>Vận chuyển</span><span class="money">{{ number_format($order->shipping_fee, 0, ',', '.') }} ₫</span></div><div class="summary__row summary__total"><span>Tổng cộng</span><span class="money">{{ number_format($order->total, 0, ',', '.') }} ₫</span></div><a class="btn" href="{{ route('account.order', $order) }}">Xem chi tiết đơn</a></aside>
    </div>
</div>
@endsection
