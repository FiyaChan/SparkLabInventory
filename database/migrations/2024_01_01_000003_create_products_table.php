<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // restrictOnDelete: prevents deleting a category that still has products.
            // Forces admin to reassign products first — protects referential integrity.
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

            $table->string('sku')->unique(); // Stock Keeping Unit — business-facing unique code
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // decimal, NEVER float, for money — avoids floating point rounding errors
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable(); // for profit margin reports

            $table->boolean('is_active')->default(true); // soft "publish/unpublish" toggle
            $table->timestamps();
            $table->softDeletes(); // recoverable delete — preserves order_items history

            $table->index(['category_id', 'is_active']); // speeds up storefront category filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
