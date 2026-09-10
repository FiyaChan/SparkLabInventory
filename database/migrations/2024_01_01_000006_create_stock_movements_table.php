<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This table is append-only: we never UPDATE or DELETE rows here.
        // It is the single source of truth for "Stock History" and lets us
        // recompute quantity_on_hand at any point in time if inventory
        // ever gets out of sync (a form of data integrity self-check).
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['stock_in', 'stock_out', 'adjustment']);
            $table->integer('quantity'); // positive for in, negative for out/adjustment-down
            $table->string('reason')->nullable(); // e.g. "Purchase order #123", "Damaged goods"

            // Who performed the action — required for the audit trail / accountability
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']); // fast "history for this product" queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
