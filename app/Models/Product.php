<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'description',
        'price', 'cost_price', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->where('is_active', true);
    }

    // Convenience accessor: $product->is_low_stock
    public function isLowStock(): bool
    {
        return $this->inventory
            && $this->inventory->quantity_on_hand <= $this->inventory->reorder_level;
    }

    /**
     * Standardized formatted price or price range (e.g. "RM 35.00 - RM 120.00").
     */
    public function getFormattedPriceAttribute(): string
    {
        $hasVars = $this->relationLoaded('variations') ? $this->variations->isNotEmpty() : $this->variations()->exists();

        if ($hasVars) {
            $vars = $this->relationLoaded('variations') ? $this->variations : $this->variations()->get();
            $min = (float) $vars->min('price');
            $max = (float) $vars->max('price');

            if ($min !== $max) {
                return 'RM ' . number_format($min, 2) . ' - RM ' . number_format($max, 2);
            }

            return 'RM ' . number_format($min, 2);
        }

        return 'RM ' . number_format($this->price, 2);
    }

    /**
     * Total available physical stock across all variations or base inventory.
     */
    public function getTotalStockAttribute(): int
    {
        $hasVars = $this->relationLoaded('variations') ? $this->variations->isNotEmpty() : $this->variations()->exists();

        if ($hasVars) {
            $vars = $this->relationLoaded('variations') ? $this->variations : $this->variations()->get();
            return (int) $vars->sum('stock');
        }

        return (int) ($this->inventory->quantity_on_hand ?? 0);
    }
}
