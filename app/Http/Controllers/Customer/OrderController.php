<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        // Scoped to Auth::id() — a customer can only ever see their own
        // orders. This scoping happens at the query level (not just hidden
        // in the UI), so there's no way to view someone else's order history
        // just by guessing an order id in the URL — see show() below for the
        // same protection on the detail view.
        $orders = Order::with(['items', 'payment'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        // abort_unless() enforces ownership — this is the critical check.
        // Without it, /orders/{id} would be an Insecure Direct Object
        // Reference (IDOR) vulnerability: any logged-in user could view
        // any other user's order just by changing the id in the URL.
        abort_unless($order->user_id === Auth::id(), 403);

        $order->load('items.product', 'payment');

        return view('customer.orders.show', compact('order'));
    }
}
