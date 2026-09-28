@extends('layouts.app')

@section('title', 'Thanh toán · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head"><h1 class="page-title">Thanh toán</h1><p class="page-lede">Dữ liệu thẻ được gửi thẳng tới Payment Service để token hóa. Shop không lưu số thẻ đầy đủ hoặc CVV.</p></header>
    <form class="checkout-layout" method="post" action="{{ route('checkout.store') }}" x-data="submitState" @submit="submit">
        @csrf
        <div class="form-grid">
            <h2 class="section-title">Người nhận</h2>
            <label class="field"><span class="field__label">Họ tên</span><input class="field__control" name="recipient_name" value="{{ old('recipient_name', $address?->recipient_name ?? auth()->user()->name) }}" autocomplete="name" required></label>
            <label class="field"><span class="field__label">Số điện thoại</span><input class="field__control" name="phone" value="{{ old('phone', $address?->phone ?? auth()->user()->phone) }}" inputmode="tel" autocomplete="tel" required></label>
            <label class="field"><span class="field__label">Địa chỉ</span><input class="field__control" name="line1" value="{{ old('line1', $address?->line1) }}" autocomplete="street-address" required></label>
            <div class="filter-row">
                <label class="field"><span class="field__label">Phường/Xã</span><input class="field__control" name="ward" value="{{ old('ward', $address?->ward) }}" required></label>
                <label class="field"><span class="field__label">Quận/Huyện</span><input class="field__control" name="district" value="{{ old('district', $address?->district) }}" required></label>
                <label class="field"><span class="field__label">Tỉnh/Thành</span><input class="field__control" name="city" value="{{ old('city', $address?->city) }}" required></label>
            </div>

            <h2 class="section-title">Phương thức thanh toán</h2>
            <label class="field"><span class="field__label">Phương thức</span><select class="field__control" name="payment_method" x-model="method"><option value="card">Thẻ test</option><option value="cod">Thanh toán khi nhận hàng</option></select></label>
            @if($cards->isNotEmpty())
                <label class="field"><span class="field__label">Thẻ đã lưu</span><select class="field__control" name="credit_card_id"><option value="">Dùng thẻ test mới</option>@foreach($cards as $card)<option value="{{ $card->id }}">{{ strtoupper($card->card_type) }} ·•••• {{ $card->last_four }} · {{ $card->expiration }}</option>@endforeach</select><span class="field__help">Để trống nếu muốn nhập thẻ test khác.</span></label>
            @endif
            <div class="filter-row">
                <label class="field"><span class="field__label">Số thẻ test</span><input class="field__control" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4242424242424242"><span class="field__help">Chỉ dùng số thẻ test trong tài liệu.</span></label>
                <label class="field"><span class="field__label">Hạn thẻ</span><input class="field__control" name="expiration" inputmode="numeric" autocomplete="cc-exp" placeholder="12/30"></label>
                <label class="field"><span class="field__label">CVV test</span><input class="field__control" name="cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123"></label>
            </div>
            <label class="cluster"><input type="checkbox" name="save_card" value="1"> Lưu token thẻ để dùng lại</label>
            <label class="field"><span class="field__label">Ghi chú</span><textarea class="field__control" name="notes" maxlength="500" placeholder="Ví dụ: gọi trước khi giao"></textarea></label>
        </div>
        <aside class="summary">
            @foreach($cart->items as $item)<div class="summary__row"><span>{{ $item->product->name }} × {{ $item->quantity }}</span><span class="money">{{ number_format($item->product->price * $item->quantity, 0, ',', '.') }} ₫</span></div>@endforeach
            <div class="summary__row summary__total"><span>Tạm tính</span><span class="money">{{ number_format($cart->total, 0, ',', '.') }} ₫</span></div>
            <p class="muted">Phí vận chuyển do mock shipping tính khi xác nhận.</p>
            <button class="btn" type="submit" :data-state="state">Xác nhận đơn hàng</button>
        </aside>
    </form>
</div>
@endsection
