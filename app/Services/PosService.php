<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockMovement;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PosService
{
    /**
     * Process a complete Point of Sale transaction with concurrency locking,
     * server-authoritative pricing, stock deduction, and payment capture.
     *
     * @param  array  $data
     * @return array
     * @throws Exception
     */
    public function processSale(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $itemsPayload = $data['items'] ?? [];
            if (empty($itemsPayload)) {
                throw new RuntimeException('No cart items provided for POS checkout.');
            }

            // Step 1: Customer identification
            $customer = null;
            if (! empty($data['customer_id'])) {
                $customer = User::find($data['customer_id']);
            }

            if (! $customer) {
                // Find or create the default Walk-in Customer account
                $customer = User::firstOrCreate(
                    ['email' => 'walkin@pos.local'],
                    [
                        'name' => 'Walk-in Customer',
                        'password' => Hash::make('WalkinCustomerPos123!'),
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]
                );
                if (! $customer->hasRole('customer')) {
                    $customer->assignRole('customer');
                }
            }

            $customerName = ! empty($data['customer_name']) ? $data['customer_name'] : $customer->name;
            $customerPhone = ! empty($data['customer_phone']) ? $data['customer_phone'] : ($customer->phone ?? 'N/A');

            // Step 2: Calculate server-authoritative line items and validate inventory with lockForUpdate
            $processedItems = [];
            $subtotal = 0.00;

            foreach ($itemsPayload as $item) {
                $productId = (int) $item['product_id'];
                $variationId = ! empty($item['variation_id']) ? (int) $item['variation_id'] : null;
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                // Fetch product directly from DB
                $product = Product::where('id', $productId)
                    ->where('is_active', true)
                    ->first();

                if (! $product) {
                    throw new RuntimeException("Product ID #{$productId} is not available for sale.");
                }

                // Concurrency safe inventory lock
                $inventory = Inventory::where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                $variation = null;
                $variantName = null;
                $unitPrice = (float) $product->price;

                if ($variationId) {
                    $variation = ProductVariation::where('id', $variationId)
                        ->where('product_id', $productId)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $variation) {
                        throw new RuntimeException("Selected variation for '{$product->name}' is no longer available.");
                    }

                    if ($variation->stock < $quantity) {
                        throw new RuntimeException("Insufficient stock for '{$product->name} - {$variation->name}'. Available: {$variation->stock}, Requested: {$quantity}.");
                    }

                    $unitPrice = (float) $variation->price;
                    $variantName = $variation->name;
                } else {
                    if (! $inventory) {
                        throw new RuntimeException("Inventory record not found for product: {$product->name}.");
                    }

                    if ($inventory->quantity_on_hand < $quantity) {
                        throw new RuntimeException("Insufficient stock for '{$product->name}' (SKU: {$product->sku}). Available: {$inventory->quantity_on_hand}, Requested: {$quantity}.");
                    }
                }

                $lineSubtotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineSubtotal;

                $processedItems[] = [
                    'product' => $product,
                    'variation' => $variation,
                    'variation_id' => $variationId,
                    'variant_name' => $variantName,
                    'inventory' => $inventory,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (empty($processedItems)) {
                throw new RuntimeException('No valid items found in the cart.');
            }

            // Step 3: Compute Discounts & Grand Total
            $subtotal = round($subtotal, 2);
            $discount = 0.00;
            $discountType = $data['discount_type'] ?? 'fixed';
            $discountValue = isset($data['discount_value']) ? (float) $data['discount_value'] : 0.00;

            if ($discountValue > 0) {
                if ($discountType === 'percentage') {
                    $discount = round(($subtotal * min(100, $discountValue)) / 100, 2);
                } else {
                    $discount = round(min($subtotal, $discountValue), 2);
                }
            }

            $grandTotal = max(0.00, round($subtotal - $discount, 2));

            // Step 4: Payment Verification & Change Calculation
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $tenderedAmount = isset($data['tendered_amount']) ? (float) $data['tendered_amount'] : $grandTotal;
            $changeDue = 0.00;

            if ($paymentMethod === 'cash') {
                if ($tenderedAmount < $grandTotal) {
                    throw new RuntimeException("Tendered amount (RM ".number_format($tenderedAmount, 2).") is less than the grand total (RM ".number_format($grandTotal, 2).").");
                }
                $changeDue = round($tenderedAmount - $grandTotal, 2);
            } else {
                $tenderedAmount = $grandTotal;
                $changeDue = 0.00;
            }

            // Step 5: Generate Unique POS Order Number
            $datePrefix = date('Ymd');
            $uniqueSuffix = strtoupper(Str::random(5));
            $orderNumber = "POS-{$datePrefix}-{$uniqueSuffix}";

            // Step 6: Create Order Record
            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => $orderNumber,
                'status' => 'completed',
                'source' => 'pos',
                'total_amount' => $grandTotal,
                'shipping_name' => $customerName,
                'shipping_phone' => $customerPhone,
                'shipping_address' => 'POS Counter Direct Sale',
            ]);

            // Step 7: Create Order Items & Deduct Inventory
            $cashierId = Auth::id() ?? 1;

            foreach ($processedItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'variation_id' => $item['variation_id'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Decrement stock
                if ($item['variation']) {
                    $item['variation']->decrement('stock', $item['quantity']);
                }
                if ($item['inventory']) {
                    $item['inventory']->decrement('quantity_on_hand', $item['quantity']);
                }

                // Log Stock Movement
                $reason = "POS Walk-in Sale - {$order->order_number}";
                if ($item['variant_name']) {
                    $reason .= " ({$item['variant_name']})";
                }

                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'stock_out',
                    'quantity' => -1 * $item['quantity'],
                    'reason' => $reason,
                    'performed_by' => $cashierId,
                ]);
            }

            // Step 8: Create Payment Record
            $payment = Payment::create([
                'order_id' => $order->id,
                'method' => $paymentMethod,
                'status' => 'paid',
                'transaction_ref' => 'POS-TXN-' . strtoupper(Str::random(10)),
                'amount' => $grandTotal,
            ]);

            // Step 9: Audit Trail
            ActivityLog::record('pos.order_completed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $order->total_amount,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'payment_method' => $paymentMethod,
                'tendered' => $tenderedAmount,
                'change' => $changeDue,
                'items_count' => count($processedItems),
                'cashier_id' => $cashierId,
            ]);

            return [
                'order' => $order->fresh(['items.product', 'payment', 'user']),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grand_total' => $grandTotal,
                'tendered_amount' => $tenderedAmount,
                'change_due' => $changeDue,
                'payment_method' => $paymentMethod,
            ];
        });
    }
}
