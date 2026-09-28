@extends('layouts.app')
@section('title', 'Đặt lại mật khẩu · Hugo Shop')
@section('content')
<div class="page shell"><section class="auth-card"><h1>Đặt lại mật khẩu</h1><form class="form-grid" method="post" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><label class="field"><span class="field__label">Email</span><input class="field__control" type="email" name="email" value="{{ old('email', $email) }}" required></label><label class="field"><span class="field__label">Mật khẩu mới</span><input class="field__control" type="password" name="password" required></label><label class="field"><span class="field__label">Nhập lại mật khẩu</span><input class="field__control" type="password" name="password_confirmation" required></label><button class="btn" type="submit">Lưu mật khẩu mới</button></form></section></div>
@endsection
