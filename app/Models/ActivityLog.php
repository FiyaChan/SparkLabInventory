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
    public static function record(string $action, array $description = []): ?self
    {
        // 1. Resolve user ID: check $description['user_id'] first, then auth()->id() if logged in
        $userId = $description['user_id'] ?? (auth()->check() ? auth()->id() : null);

        // 2. Verify the user actually exists to avoid foreign key violations with stale session cookies
        if ($userId !== null && ! User::where('id', $userId)->exists()) {
            $userId = null;
        }

        try {
            return self::create([
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
