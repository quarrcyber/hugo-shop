<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || in_array($user->role, ['staff', 'admin'], true);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && in_array($order->status, ['pending', 'paid', 'processing'], true);
    }
}
