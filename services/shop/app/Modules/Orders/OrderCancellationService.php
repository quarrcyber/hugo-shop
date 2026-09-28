<?php

namespace App\Modules\Orders;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Modules\Payments\PaymentClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationService
{
    public function __construct(private PaymentClient $payments) {}

    public function cancel(Order $order, User $actor, ?string $ip = null, ?string $userAgent = null): Order
    {
        if (! in_array($order->status, ['pending', 'paid', 'processing'], true)) {
            throw ValidationException::withMessages(['order' => 'Đơn hàng ở trạng thái hiện tại không thể hủy.']);
        }

        $payment = $order->payments()->where('status', 'succeeded')->latest()->first();
        $refund = $payment?->transaction_id ? $this->payments->refund($payment->transaction_id) : null;

        return DB::transaction(function () use ($order, $actor, $ip, $userAgent, $payment, $refund): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();
            if (! in_array($locked->status, ['pending', 'paid', 'processing'], true)) {
                throw ValidationException::withMessages(['order' => 'Đơn hàng đã được xử lý bởi yêu cầu khác.']);
            }

            foreach ($locked->items as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
            if ($payment && $refund) {
                $payment->update(['status' => 'refunded', 'gateway_response' => $refund]);
            }
            $locked->update(['status' => $payment ? 'refunded' : 'cancelled']);
            AuditLog::query()->create([
                'user_id' => $actor->id,
                'action' => 'order.cancelled',
                'auditable_type' => Order::class,
                'auditable_id' => $locked->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'metadata' => ['refunded' => (bool) $payment],
            ]);

            return $locked->fresh(['items', 'payments']);
        });
    }
}
