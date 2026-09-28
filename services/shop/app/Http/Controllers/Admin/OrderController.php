<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Modules\Orders\OrderCancellationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders', ['orders' => Order::query()->with(['user', 'payments'])->latest()->paginate(25)]);
    }

    public function update(Request $request, Order $order, OrderCancellationService $cancellations): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['processing', 'shipped', 'completed', 'cancelled'])], 'tracking_code' => ['nullable', 'string', 'max:80']]);
        $allowed = [
            'pending' => ['cancelled'],
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [],
            'cancelled' => [],
            'refunded' => [],
        ];
        abort_unless(in_array($data['status'], $allowed[$order->status], true), 422, 'Chuyển trạng thái không hợp lệ.');
        if ($data['status'] === 'cancelled') {
            $cancellations->cancel($order, $request->user(), $request->ip(), $request->userAgent());

            return back()->with('success', 'Đơn hàng đã được hủy.');
        }
        if ($data['status'] === 'shipped' && empty($data['tracking_code'])) {
            return back()->withErrors(['tracking_code' => 'Cần mã vận đơn trước khi chuyển sang trạng thái đã giao vận.']);
        }
        $before = $order->status;
        $order->update($data);
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'order.status.updated',
            'auditable_type' => Order::class,
            'auditable_id' => $order->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['from' => $before, 'to' => $order->status],
        ]);

        return back()->with('success', 'Trạng thái đơn hàng đã được cập nhật.');
    }
}
