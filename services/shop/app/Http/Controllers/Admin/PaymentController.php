<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Modules\Payments\PaymentClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.payments', ['payments' => Payment::query()->with(['order', 'user'])->latest()->paginate(25)]);
    }

    public function refund(Request $request, Payment $payment, PaymentClient $client): RedirectResponse
    {
        abort_unless($payment->status === 'succeeded' && $payment->transaction_id, 422, 'Giao dịch không thể hoàn tiền.');
        $result = $client->refund($payment->transaction_id);
        $payment->update(['status' => 'refunded', 'gateway_response' => $result]);
        $payment->order()->update(['status' => 'refunded']);
        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'payment.refunded',
            'auditable_type' => Payment::class,
            'auditable_id' => $payment->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['refund_id' => $result['refund_id']],
        ]);

        return back()->with('success', 'Giao dịch đã được hoàn tiền.');
    }
}
