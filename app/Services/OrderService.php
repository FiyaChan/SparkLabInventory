<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(protected StockService $stockService) {}

    /**
     * Converts a cart into an order. This is the single most important
     * transaction in the whole system, so every line here is deliberate:
     *
     * 1. Never trust prices from the client — always re-read from the DB.
     * 2. Never trust "is in stock" from the cart page — re-check live,
     *    inside the same locked transaction as the stock deduction.
     * 3. All-or-nothing: if ANY item fails (e.g. someone else bought the
     *    last unit seconds ago), the entire order is rolled back — the
     *    customer isn't left with a half-fulfilled order.
     */
    public function placeOrder(User $user, Cart $cart, array $shippingDetails, string $paymentMethod): Order
    {
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($user, $cart, $shippingDetails, $paymentMethod) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'total_amount' => 0, // computed below, updated before commit
                'shipping_name' => $shippingDetails['shipping_name'],
                'shipping_phone' => $shippingDetails['shipping_phone'],
                'shipping_address' => $shippingDetails['shipping_address'],
            ]);

            $total = 0;

            foreach ($cart->items as $item) {
                // Re-fetch the product fresh inside the transaction, with a row
                // lock — the cart's eager-loaded $item->product could be a stale
                // read from before this transaction started.
                $product = $item->product()->lockForUpdate()->first();

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => "\"{$product->name}\" is no longer available.",
                    ]);
                }

                // The price used is ALWAYS product.price read server-side right now —
                // never a price submitted from the checkout form. This is what stops
                // a tampered request from checking out at an attacker-chosen price.
                $unitPrice = $product->price;
                $subtotal = $unitPrice * $item->quantity;
                $total += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Deduct stock through StockService so it's captured in the
                // stock_movements audit ledger like every other stock change —
                // an order is just another kind of "stock_out". This also gets
                // us the row-locking + negative-stock guard for free.
                $this->stockService->recordMovement(
                    $product,
                    'stock_out',
                    $item->quantity,
                    "Order {$order->order_number}",
                    $user
                );
            }

            $order->update(['total_amount' => $total]);

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                // COD stays 'pending' until the courier collects payment on delivery.
                // The simulated online gateway "succeeds" immediately for demo purposes —
                // in a real integration this would instead be 'pending' until a
                // webhook confirms payment.
                'status' => $paymentMethod === 'cod' ? 'pending' : 'paid',
                'transaction_ref' => $paymentMethod === 'online_simulation'
                    ? 'SIM-'.Str::upper(Str::random(12))
                    : null,
                'amount' => $total,
            ]);

            // Empty the cart only after everything above succeeded.
            $cart->items()->delete();

            ActivityLog::record('order.placed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $total,
            ]);

            return $order->fresh(['items.product', 'payment']);
        });
    }

    protected function generateOrderNumber(): string
    {
        // NOTE: under very high concurrency this count-based approach could
        // theoretically collide; the unique constraint on orders.order_number
        // guarantees we'd never silently create a duplicate, at worst the
        // DB throws and the transaction rolls back cleanly. For a FYP-scale
        // system this simple approach is appropriate; a production system
        // would use a dedicated sequence table instead.
        return 'ORD-'.now()->format('Y').'-'.str_pad(
            (Order::whereYear('created_at', now()->year)->count() + 1),
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    public function updateStatus(Order $order, string $status, User $performedBy): void
    {
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

        if (! in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid order status.']);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        ActivityLog::record('order.status_changed', [
            'order_id' => $order->id,
            'from' => $oldStatus,
            'to' => $status,
            'by' => $performedBy->id,
        ]);
    }
}
