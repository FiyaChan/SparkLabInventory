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
    public function __construct(
        protected StockService $stockService,
        protected ?EInvoiceService $eInvoiceService = null
    ) {
        $this->eInvoiceService = $eInvoiceService ?? app(EInvoiceService::class);
    }

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
            $buyerTin = $shippingDetails['buyer_tin'] ?? ($user->tin ?: 'EI00000000020');
            $buyerIdType = $shippingDetails['buyer_id_type'] ?? ($user->id_type ?: ($buyerTin === 'EI00000000020' ? 'GENERAL_PUBLIC' : 'NRIC'));
            $buyerIdNum = $shippingDetails['buyer_id_number'] ?? ($user->id_number ?: '000000000000');
            $buyerSst = $shippingDetails['buyer_sst_no'] ?? ($user->sst_number ?: null);
            $requireEinvoice = ! empty($shippingDetails['require_einvoice']);

            // Auto-update user profile with tax details if newly provided
            if (! empty($shippingDetails['buyer_tin']) && empty($user->tin)) {
                $user->update([
                    'tin' => $shippingDetails['buyer_tin'],
                    'id_type' => $buyerIdType,
                    'id_number' => $buyerIdNum,
                    'sst_number' => $buyerSst,
                ]);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'source' => 'web',
                'total_amount' => 0, // computed below, updated before commit
                'shipping_name' => $shippingDetails['shipping_name'],
                'shipping_phone' => $shippingDetails['shipping_phone'],
                'shipping_address' => $shippingDetails['shipping_address'],
                'buyer_tin' => $buyerTin,
                'buyer_id_type' => $buyerIdType,
                'buyer_id_number' => $buyerIdNum,
                'buyer_sst_no' => $buyerSst,
                'require_einvoice' => $requireEinvoice,
            ]);

            $total = 0;

            foreach ($cart->items as $item) {
                // Re-fetch the product fresh inside the transaction, with a row lock
                $product = $item->product()->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => "\"{$item->product->name}\" is no longer available.",
                    ]);
                }

                $variation = null;
                $variantName = null;
                $unitPrice = (float) $product->price;

                if ($item->variation_id) {
                    $variation = \App\Models\ProductVariation::where('id', $item->variation_id)
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $variation) {
                        throw ValidationException::withMessages([
                            'cart' => "The selected variation for \"{$product->name}\" is no longer available.",
                        ]);
                    }

                    if ($variation->stock < $item->quantity) {
                        throw ValidationException::withMessages([
                            'cart' => "Insufficient stock for \"{$product->name} - {$variation->name}\". Only {$variation->stock} available.",
                        ]);
                    }

                    $unitPrice = (float) $variation->price;
                    $variantName = $variation->name;

                    // Deduct variation stock
                    $variation->decrement('stock', $item->quantity);

                    // Also decrement general inventory snapshot if available
                    if ($product->inventory) {
                        $product->inventory()->decrement('quantity_on_hand', min($product->inventory->quantity_on_hand, $item->quantity));
                    }
                } else {
                    // Deduct stock through StockService so it's captured in the stock_movements audit ledger
                    $this->stockService->recordMovement(
                        $product,
                        'stock_out',
                        $item->quantity,
                        "Order {$order->order_number}",
                        $user
                    );
                }

                $subtotal = round($unitPrice * $item->quantity, 2);
                $total += $subtotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'variation_id' => $item->variation_id,
                    'variant_name' => $variantName,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $total]);

            $initialPaymentStatus = in_array($paymentMethod, ['cod', 'toyyibpay'], true) ? 'pending' : 'paid';

            Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                // COD stays 'pending' until the courier collects payment on delivery.
                // ToyyibPay stays 'pending' until the customer completes FPX bank transfer.
                // The simulated online gateway "succeeds" immediately for demo purposes.
                'status' => $initialPaymentStatus,
                'transaction_ref' => $paymentMethod === 'online_simulation'
                    ? 'SIM-'.Str::upper(Str::random(12))
                    : null,
                'amount' => $total,
            ]);

            // If payment succeeded immediately (e.g. online simulation), generate e-invoice now
            if ($initialPaymentStatus === 'paid') {
                try {
                    $this->eInvoiceService->generateForOrder($order, [
                        'buyer_tin' => $buyerTin,
                        'buyer_id_type' => $buyerIdType,
                        'buyer_id_value' => $buyerIdNum,
                        'buyer_sst_no' => $buyerSst,
                        'buyer_name' => $order->shipping_name,
                        'buyer_phone' => $order->shipping_phone,
                        'buyer_email' => $user->email,
                        'buyer_address' => $order->shipping_address,
                    ]);
                } catch (\Throwable $e) {
                    // Non-blocking for checkout transaction
                }
            }

            // Empty the cart only after everything above succeeded.
            $cart->items()->delete();

            ActivityLog::record('order.placed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $total,
            ]);

            return $order->fresh(['items.product', 'payment', 'eInvoice']);
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
