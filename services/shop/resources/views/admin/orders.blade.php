@extends('layouts.app')

@section('title', 'Quản lý đơn hàng · Hugo Shop')

@section('content')
@php
    $transitions = [
        'pending' => ['cancelled' => 'Hủy'],
        'paid' => ['processing' => 'Đang xử lý', 'cancelled' => 'Hủy và hoàn tiền'],
        'processing' => ['shipped' => 'Đã giao vận', 'cancelled' => 'Hủy'],
        'shipped' => ['completed' => 'Hoàn tất'],
        'completed' => [],
        'cancelled' => [],
        'refunded' => [],
    ];
@endphp
<div class="page shell">
    <header class="page-head">
        <h1 class="page-title">Đơn hàng</h1>
        <p class="page-lede">Cập nhật trạng thái và mã vận đơn. Mỗi thay đổi được ghi vào audit log.</p>
    </header>
    @include('admin._nav')
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Mã</th><th>Khách</th><th>Tổng</th><th>Trạng thái</th><th>Thanh toán</th><th>Cập nhật</th></tr></thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td class="money">{{ number_format($order->total, 0, ',', '.') }} ₫</td>
                        <td><span class="badge">{{ $order->status }}</span></td>
                        <td>{{ $order->payments->last()?->status ?? '—' }}</td>
                        <td>
                            @if($transitions[$order->status])
                                <form class="cluster" method="post" action="{{ route('admin.orders.update', $order) }}">
                                    @csrf
                                    @method('patch')
                                    <select class="field__control" name="status" aria-label="Trạng thái tiếp theo">
                                        @foreach($transitions[$order->status] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <input class="field__control" name="tracking_code" value="{{ $order->tracking_code }}" placeholder="Mã vận đơn">
                                    <button class="btn btn--quiet" type="submit">Lưu</button>
                                </form>
                            @else
                                <span class="muted">Không còn bước xử lý</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $orders->links() }}</div>
</div>
@endsection
