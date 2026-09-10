<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        // Owner can always view their own order. Staff/admin with the
        // 'order.view' permission can view any order (for fulfillment).
        return $order->user_id === $user->id || $user->can('order.view');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->can('order.update-status');
    }
}
