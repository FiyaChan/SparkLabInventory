<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // restrictOnDelete: never allow deleting a user who has order history.
            // Deactivate the account instead (see users.is_active).
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('order_number')->unique(); // human-friendly ref, e.g. ORD-2026-00001
            $table->enum('status', ['pending', 'processing', 'shipped', 'completed', 'cancelled'])
                  ->default('pending');

            $table->decimal('total_amount', 10, 2);

            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->text('shipping_address');

            $table->timestamps();

            $table->index('status'); // admin dashboard filters heavily by status
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // restrictOnDelete: a product that has been ordered can be soft-deleted
            // (see products.softDeletes) but never hard-deleted, to preserve order history.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            $table->unsignedInteger('quantity');

            // Snapshot the price AT TIME OF ORDER. If the product price changes later,
            // historical orders must still show what the customer actually paid.
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
