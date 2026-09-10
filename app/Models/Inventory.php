<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $table = 'inventory'; // Eloquent would otherwise guess "inventories"

    protected $fillable = ['product_id', 'quantity_on_hand', 'reorder_level'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}