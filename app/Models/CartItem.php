<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'variation_id', 'quantity'];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function getUnitPriceAttribute(): float
    {
        if ($this->variation) {
            return (float) $this->variation->price;
        }

        return (float) ($this->product->price ?? 0);
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->unit_price * $this->quantity, 2);
    }

    public function getMaxAvailableStockAttribute(): int
    {
        if ($this->variation) {
            return (int) $this->variation->stock;
        }

        return (int) ($this->product->inventory->quantity_on_hand ?? 0);
    }
}