<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'source', 'total_amount',
        'shipping_name', 'shipping_phone', 'shipping_address',
        'buyer_tin', 'buyer_id_type', 'buyer_id_number', 'buyer_sst_no', 'require_einvoice',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'require_einvoice' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function eInvoice(): HasOne
    {
        return $this->hasOne(EInvoice::class);
    }
}