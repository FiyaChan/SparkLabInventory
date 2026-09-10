<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    const UPDATED_AT = null; // logs are write-once, never updated

    protected $fillable = ['user_id', 'action', 'ip_address', 'user_agent', 'description'];

    protected function casts(): array
    {
        return ['description' => 'array']; // auto-encode/decode JSON column
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience static helper so any controller/service can log in one line:
     * ActivityLog::record('product.deleted', ['product_id' => $product->id]);
     */
    public static function record(string $action, array $description = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }
}
