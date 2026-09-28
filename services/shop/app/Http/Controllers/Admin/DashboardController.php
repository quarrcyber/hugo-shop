<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $revenue = Order::query()->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])->sum('total');
        $series = Order::query()
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();

        return view('admin.dashboard', [
            'metrics' => [
                'revenue' => $revenue,
                'orders' => Order::query()->count(),
                'customers' => User::query()->where('role', 'customer')->count(),
                'low_stock' => Product::query()->where('stock', '<=', 5)->count(),
                'pending_reviews' => Review::query()->where('status', 'pending')->count(),
                'failed_payments' => Payment::query()->where('status', 'failed')->count(),
            ],
            'series' => $series,
            'orders' => Order::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }
}
