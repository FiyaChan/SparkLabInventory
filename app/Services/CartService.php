<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Gets or creates the user's single cart. Every customer has exactly one
     * cart (enforced by the unique constraint on carts.user_id).
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Product $product, int $quantity, ?int $variationId = null): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is not currently available.']);
        }

        $variation = null;
        if ($variationId) {
            $variation = ProductVariation::where('id', $variationId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variation) {
                throw ValidationException::withMessages(['variation' => 'Selected product variation is invalid or unavailable.']);
            }
        } elseif ($product->variations()->where('is_active', true)->exists()) {
            // Product has variations but none specified: default to first active variation
            $variation = $product->variations()->where('is_active', true)->first();
            $variationId = $variation?->id;
        }

        $cart = $this->getOrCreateCart($user);

        DB::transaction(function () use ($cart, $product, $quantity, $variationId, $variation) {
            $existing = $cart->items()
                ->where('product_id', $product->id)
                ->where(function ($q) use ($variationId) {
                    if ($variationId) {
                        $q->where('variation_id', $variationId);
                    } else {
                        $q->whereNull('variation_id');
                    }
                })
                ->first();

            $newQuantity = ($existing->quantity ?? 0) + $quantity;

            $available = $variation ? (int) $variation->stock : (int) ($product->inventory->quantity_on_hand ?? 0);
            $displayName = $variation ? "{$product->name} ({$variation->name})" : $product->name;

            if ($newQuantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$available} units of \"{$displayName}\" are available in stock.",
                ]);
            }

            if ($existing) {
                $existing->update(['quantity' => $newQuantity]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'variation_id' => $variationId,
                    'quantity' => $newQuantity,
                ]);
            }
        });
    }

    public function updateQuantity(User $user, int $cartItemId, int $quantity): void
    {
        $cart = $this->getOrCreateCart($user);
        $item = $cart->items()->with(['product.inventory', 'variation'])->find($cartItemId);

        if (! $item) {
            return;
        }

        if ($quantity <= 0) {
            $item->delete();
            return;
        }

        $available = $item->max_available_stock;
        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} units available in stock.",
            ]);
        }

        $item->update(['quantity' => $quantity]);
    }

    public function removeItem(User $user, int $cartItemId): void
    {
        $cart = $this->getOrCreateCart($user);
        $cart->items()->where('id', $cartItemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
