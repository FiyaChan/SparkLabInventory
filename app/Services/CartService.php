<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Gets or creates the user's single cart. Every customer has exactly one
     * cart (enforced by the unique constraint on carts.user_id), so there's
     * never ambiguity about "which cart" — unlike guest/session-based carts
     * which need merging logic on login.
     */
    public function getOrCreateCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function addItem(User $user, Product $product, int $quantity): void
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is not currently available.']);
        }

        $cart = $this->getOrCreateCart($user);

        DB::transaction(function () use ($cart, $product, $quantity) {
            $existing = $cart->items()->where('product_id', $product->id)->first();
            $newQuantity = ($existing->quantity ?? 0) + $quantity;

            // Cap at available stock. We check this again at checkout time too —
            // stock can change between "add to cart" and "checkout", so this is
            // a UX convenience, not the final authority (that's in the checkout flow).
            $available = $product->inventory->quantity_on_hand ?? 0;
            if ($newQuantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$available} units of \"{$product->name}\" are available.",
                ]);
            }

            $cart->items()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $newQuantity]
            );
        });
    }

    public function updateQuantity(User $user, Product $product, int $quantity): void
    {
        $cart = $this->getOrCreateCart($user);

        if ($quantity <= 0) {
            $cart->items()->where('product_id', $product->id)->delete();
            return;
        }

        $available = $product->inventory->quantity_on_hand ?? 0;
        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} units available.",
            ]);
        }

        $cart->items()->updateOrCreate(
            ['product_id' => $product->id],
            ['quantity' => $quantity]
        );
    }

    public function removeItem(User $user, Product $product): void
    {
        $cart = $this->getOrCreateCart($user);
        $cart->items()->where('product_id', $product->id)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
