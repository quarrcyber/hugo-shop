@extends('layouts.app')
@section('title', 'Quên mật khẩu · Hugo Shop')
@section('content')
<div class="page shell"><section class="auth-card"><h1>Quên mật khẩu</h1><p class="muted" style="margin-block-end: var(--space-lg)">Nhập email để nhận liên kết đặt lại mật khẩu qua Mailpit.</p><form class="form-grid" method="post" action="{{ route('password.email') }}">@csrf<label class="field"><span class="field__label">Email</span><input class="field__control" type="email" name="email" value="{{ old('email') }}" required></label><button class="btn" type="submit">Gửi liên kết</button></form></section></div>
@endsection
