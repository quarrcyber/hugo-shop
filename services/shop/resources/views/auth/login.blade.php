@extends('layouts.app')
@section('title', 'Đăng nhập · Hugo Shop')
@section('content')
<div class="page shell"><section class="auth-card"><h1>Đăng nhập</h1><form class="form-grid" method="post" action="{{ route('login') }}">@csrf<label class="field"><span class="field__label">Email</span><input class="field__control" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label><label class="field"><span class="field__label">Mật khẩu</span><input class="field__control" type="password" name="password" autocomplete="current-password" required></label><label class="cluster"><input type="checkbox" name="remember" value="1"> Duy trì đăng nhập</label><div class="form-actions"><button class="btn" type="submit">Đăng nhập</button><a href="{{ route('password.request') }}">Quên mật khẩu?</a></div><p class="muted">Chưa có tài khoản? <a href="{{ route('register') }}">Tạo tài khoản</a>.</p></form></section></div>
@endsection
