@extends('layouts.app')

@section('title', 'Tài khoản · Hugo Shop')

@section('content')
<div class="page shell">
    <header class="page-head">
        <h1 class="page-title">Tài khoản</h1>
        <p class="page-lede">Quản lý hồ sơ, lịch sử mua hàng, sản phẩm yêu thích và các token thẻ đã lưu.</p>
    </header>

    <div class="tabs" role="navigation">
        <a href="#ho-so">Hồ sơ</a>
        <a href="#don-hang">Đơn hàng</a>
        <a href="#yeu-thich">Yêu thích</a>
        <a href="#thanh-toan">Thanh toán</a>
    </div>

    <section id="ho-so" class="section">
        <div class="section-head"><h2 class="section-title">Thông tin cá nhân</h2></div>
        <form class="form-grid" method="post" action="{{ route('account.update') }}">
            @csrf
            @method('patch')
            <label class="field">
                <span class="field__label">Họ tên</span>
                <input class="field__control" name="name" value="{{ old('name', $user->name) }}" required>
            </label>
            <label class="field">
                <span class="field__label">Email</span>
                <input class="field__control" value="{{ $user->email }}" disabled aria-disabled="true">
            </label>
            <label class="field">
                <span class="field__label">Số điện thoại</span>
                <input class="field__control" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel">
            </label>
            <button class="btn" type="submit">Lưu thay đổi</button>
        </form>
    </section>

    <section id="don-hang" class="section">
        <div class="section-head"><h2 class="section-title">Lịch sử mua hàng</h2></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Mã đơn</th><th>Ngày</th><th>Trạng thái</th><th>Tổng tiền</th><th></th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge">{{ $order->status }}</span></td>
                            <td class="money">{{ number_format($order->total, 0, ',', '.') }} ₫</td>
                            <td><a href="{{ route('account.order', $order) }}">Chi tiết →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Chưa có đơn hàng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $orders->links() }}</div>
    </section>

    <section id="yeu-thich" class="section">
        <div class="section-head"><h2 class="section-title">Sản phẩm yêu thích</h2></div>
        @if($wishlist->isEmpty())
            <p class="muted">Chưa lưu sản phẩm nào.</p>
        @else
            <div class="product-grid">
                @foreach($wishlist as $product)<x-product-card :product="$product" />@endforeach
            </div>
        @endif
    </section>

    <section id="thanh-toan" class="section">
        <div class="section-head">
            <h2 class="section-title">Phương thức thanh toán</h2>
            <p class="muted">Chỉ token, loại thẻ, bốn số cuối và hạn thẻ được lưu.</p>
        </div>

        <div class="saved-card-list">
            @forelse($user->cards as $card)
                <article class="saved-card">
                    <div>
                        <p><strong>{{ strtoupper($card->card_type) }} ·•••• {{ $card->last_four }}</strong></p>
                        <p class="muted">Hết hạn {{ $card->expiration }} @if($card->is_default)· Mặc định @endif</p>
                    </div>
                    <div class="cluster">
                        @unless($card->is_default)
                            <form method="post" action="{{ route('account.cards.update', $card) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="is_default" value="1">
                                <button class="btn btn--quiet" type="submit">Đặt mặc định</button>
                            </form>
                        @endunless
                        <form method="post" action="{{ route('account.cards.destroy', $card) }}">
                            @csrf
                            @method('delete')
                            <button class="btn btn--danger" type="submit">Xóa</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="muted">Chưa lưu token thẻ nào.</p>
            @endforelse
        </div>

        <form class="form-grid saved-card-form" method="post" action="{{ route('account.cards.store') }}" x-data="submitState" @submit="submit">
            @csrf
            <div>
                <h3>Thêm thẻ test</h3>
                <p class="muted">Chỉ dùng các số thẻ mô phỏng trong README. Không nhập dữ liệu thanh toán thật.</p>
            </div>
            <div class="filter-row">
                <label class="field">
                    <span class="field__label">Số thẻ test</span>
                    <input class="field__control" name="card_number" inputmode="numeric" autocomplete="cc-number" maxlength="16" value="{{ old('card_number') }}" required>
                </label>
                <label class="field">
                    <span class="field__label">Hạn thẻ</span>
                    <input class="field__control" name="expiration" placeholder="MM/YY" autocomplete="cc-exp" maxlength="5" value="{{ old('expiration') }}" required>
                </label>
                <label class="field">
                    <span class="field__label">CVV test</span>
                    <input class="field__control" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4" required>
                </label>
            </div>
            <button class="btn" type="submit" :data-state="state">Token hóa và lưu</button>
        </form>
    </section>
</div>
@endsection
