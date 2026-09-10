<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Every stock change in the whole system funnels through this one method.
     * Centralizing it here means: (1) we never have quantity math duplicated
     * and drifting between controllers, (2) every change is guaranteed to be
     * logged to stock_movements, (3) we can wrap it in a DB transaction so a
     * crash mid-update can never leave inventory and the ledger out of sync.
     */
    public function recordMovement(Product $product, string $type, int $quantity, string $reason, User $performedBy): StockMovement
    {
        return DB::transaction(function () use ($product, $type, $quantity, $reason, $performedBy) {
            // lockForUpdate() takes a row-level lock on the inventory row for the
            // duration of this transaction. Without this, two simultaneous requests
            // (e.g. two staff members both doing "stock out" at the same second)
            // could both read the same starting quantity and both succeed, silently
            // pushing stock negative — a classic race condition in inventory systems.
            $inventory = Inventory::where('product_id', $product->id)->lockForUpdate()->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'product_id' => $product->id,
                    'quantity_on_hand' => 0,
                    'reorder_level' => 10,
                ]);
            }

            // Convert the always-positive form input into a signed delta based on type.
            $delta = match ($type) {
                'stock_in' => $quantity,
                'stock_out', 'adjustment' => -$quantity,
                default => throw ValidationException::withMessages(['type' => 'Invalid movement type.']),
            };

            $newQuantity = $inventory->quantity_on_hand + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Cannot remove {$quantity} units — only {$inventory->quantity_on_hand} in stock.",
                ]);
            }

            $inventory->update(['quantity_on_hand' => $newQuantity]);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $delta,
                'reason' => $reason,
                'performed_by' => $performedBy->id,
            ]);

            ActivityLog::record('stock.'.$type, [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'quantity' => $delta,
                'new_balance' => $newQuantity,
            ]);

            // Fire the low-stock alert AFTER the transaction commits, not inside it —
            // we don't want a slow notification (e.g. email) to hold the DB lock open.
            if ($newQuantity <= $inventory->reorder_level) {
                DB::afterCommit(function () use ($product, $newQuantity, $inventory) {
                    $this->notifyLowStock($product, $newQuantity, $inventory->reorder_level);
                });
            }

            return $movement;
        });
    }

    protected function notifyLowStock(Product $product, int $currentQty, int $reorderLevel): void
    {
        // Notify everyone with admin or staff role — they're the ones who act on this.
        $recipients = User::role(['admin', 'staff'])->get();

        \Illuminate\Support\Facades\Notification::send(
            $recipients,
            new LowStockNotification($product, $currentQty, $reorderLevel)
        );
    }

    /**
     * Sets an absolute quantity (used for full stock-takes / audits) rather than
     * a relative delta. Internally still goes through recordMovement() as an
     * 'adjustment' so it's captured in the ledger the same way.
     */
    public function setAbsoluteQuantity(Product $product, int $newQuantity, string $reason, User $performedBy): StockMovement
    {
        $inventory = Inventory::where('product_id', $product->id)->first();
        $current = $inventory->quantity_on_hand ?? 0;
        $diff = $newQuantity - $current;

        if ($diff === 0) {
            throw ValidationException::withMessages(['quantity' => 'New quantity matches current stock — no adjustment needed.']);
        }

        return $diff > 0
            ? $this->recordMovement($product, 'stock_in', $diff, $reason, $performedBy)
            : $this->recordMovement($product, 'stock_out', abs($diff), $reason, $performedBy);
    }
}
