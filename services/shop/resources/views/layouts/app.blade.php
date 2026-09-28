<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Hugo Shop — cửa hàng thương mại điện tử mẫu chạy hoàn toàn trong môi trường local.')">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Hugo Shop')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="has-nav">
    <header class="nav-shell" x-data="siteNav" x-init="init" :class="{ 'is-compact': compact, 'is-dismissed': dismissed }">
        <div class="announcement" x-show="!dismissed">
            <p class="announcement__text">Môi trường học tập local · Không nhập dữ liệu thật · <a class="announcement__link" href="{{ route('catalog.index') }}">Xem sản phẩm →</a></p>
            <button type="button" class="announcement__close" aria-label="Ẩn thông báo" @click="dismiss">×</button>
        </div>
        <div class="main-nav">
            <div class="main-nav__inner shell">
                <a href="{{ route('home') }}" class="wordmark">Hugo Shop</a>
                <nav class="main-nav__links" aria-label="Điều hướng chính">
                    <a class="main-nav__link" href="{{ route('catalog.index') }}" @if(request()->routeIs('catalog.*')) aria-current="page" @endif>Sản phẩm</a>
                    <a class="main-nav__link" href="{{ route('home') }}#danh-muc">Danh mục</a>
                    <a class="main-nav__link" href="{{ route('home') }}#goi-y">Gợi ý</a>
                    @auth
                        <a class="main-nav__link" href="{{ route('account.index') }}" @if(request()->routeIs('account.*')) aria-current="page" @endif>Tài khoản</a>
                        @if(in_array(auth()->user()->role, ['staff', 'admin'], true))
                            <a class="main-nav__link" href="{{ route('admin.dashboard') }}">Quản trị</a>
                        @endif
                    @else
                        <a class="main-nav__link" href="{{ route('login') }}">Đăng nhập</a>
                    @endauth
                </nav>
                <div class="main-nav__actions">
                    <a class="btn btn--quiet" href="{{ route('cart.show') }}" aria-label="Giỏ hàng"><span class="cart-label-wide">Giỏ hàng</span><span class="cart-label-short">Giỏ</span></a>
                    <button type="button" class="mobile-toggle" aria-label="Mở điều hướng" :aria-expanded="open" @click="open = !open">Menu</button>
                </div>
            </div>
        </div>
        <nav class="mobile-sheet" x-cloak x-show="open" @click.outside="open = false" aria-label="Điều hướng di động">
            <a href="{{ route('catalog.index') }}">Sản phẩm</a>
            <a href="{{ route('home') }}#danh-muc">Danh mục</a>
            @auth
                <a href="{{ route('account.index') }}">Tài khoản</a>
                @if(in_array(auth()->user()->role, ['staff', 'admin'], true))<a href="{{ route('admin.dashboard') }}">Quản trị</a>@endif
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn--quiet" type="submit">Đăng xuất</button></form>
            @else
                <a href="{{ route('login') }}">Đăng nhập</a>
                <a href="{{ route('register') }}">Tạo tài khoản</a>
            @endauth
        </nav>
    </header>

    <main id="main-content">
        <div class="shell" style="padding-block-start: var(--space-md)">
            @if(session('success'))
                <div class="alert alert--success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert--error" role="alert">
                    <p>Vui lòng kiểm tra lại thông tin:</p>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
        </div>
        @yield('content')
    </main>

    <footer class="footer">
        <div class="footer__inner shell">
            <div>
                <p class="footer__wordmark">Hugo Shop</p>
                <p class="muted measure">Một cửa hàng mẫu đủ thật để học kiến trúc ứng dụng và kiểm thử bảo mật, nhưng toàn bộ dữ liệu đều là giả.</p>
            </div>
            <nav class="footer__links" aria-label="Liên kết cuối trang">
                <a href="{{ route('catalog.index') }}">Sản phẩm</a>
                <a href="{{ route('cart.show') }}">Giỏ hàng</a>
                @auth<a href="{{ route('account.index') }}">Tài khoản</a>@endauth
            </nav>
            <p class="footer__meta">© {{ date('Y') }} Hugo Shop · Chỉ dùng trong môi trường local · Không nhập thông tin thanh toán thật.</p>
        </div>
    </footer>
</body>
</html>
