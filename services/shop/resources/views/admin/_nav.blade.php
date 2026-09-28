<nav class="tabs" aria-label="Khu quản trị">
    <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Tổng quan</a>
    <a href="{{ route('admin.products.index') }}" @if(request()->routeIs('admin.products.*')) aria-current="page" @endif>Sản phẩm</a>
    <a href="{{ route('admin.orders.index') }}" @if(request()->routeIs('admin.orders.*')) aria-current="page" @endif>Đơn hàng</a>
    <a href="{{ route('admin.payments.index') }}" @if(request()->routeIs('admin.payments.*')) aria-current="page" @endif>Thanh toán</a>
    <a href="{{ route('admin.reviews.index') }}" @if(request()->routeIs('admin.reviews.*')) aria-current="page" @endif>Đánh giá</a>
    @if(auth()->user()->role === 'admin')<a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>Người dùng</a>@endif
</nav>
