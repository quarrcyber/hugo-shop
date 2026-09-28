@extends('layouts.app')
@section('title', 'Xác minh email · Hugo Shop')
@section('content')
<div class="page shell"><section class="auth-card"><h1>Xác minh email</h1><p>Liên kết xác minh đã được gửi tới email của bạn. Mở Mailpit qua đường dẫn local để kiểm tra.</p><form style="margin-block-start: var(--space-lg)" method="post" action="{{ route('verification.send') }}">@csrf<button class="btn" type="submit">Gửi lại liên kết</button></form></section></div>
@endsection
