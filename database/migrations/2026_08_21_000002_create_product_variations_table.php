<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variations')) {
            Schema::create('product_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name'); // e.g. "500ml", "Size: L", "Red Edition", "Standard Pack"
                $table->string('sku')->nullable();
                $table->decimal('price', 10, 2);
                $table->decimal('cost_price', 10, 2)->nullable();
                $table->unsignedInteger('stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (! Schema::hasColumn('order_items', 'variation_id')) {
                    $table->foreignId('variation_id')->nullable()->after('product_id')->constrained('product_variations')->nullOnDelete();
                }
                if (! Schema::hasColumn('order_items', 'variant_name')) {
                    $table->string('variant_name')->nullable()->after('variation_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (Schema::hasColumn('order_items', 'variation_id')) {
                    $table->dropForeign(['variation_id']);
                    $table->dropColumn('variation_id');
                }
                if (Schema::hasColumn('order_items', 'variant_name')) {
                    $table->dropColumn('variant_name');
                }
            });
        }

        Schema::dropIfExists('product_variations');
    }
};
