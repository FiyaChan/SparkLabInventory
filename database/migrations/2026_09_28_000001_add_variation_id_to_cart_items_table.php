<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                try {
                    $table->dropUnique(['cart_id', 'product_id']);
                } catch (\Throwable $e) {
                    // Ignore if constraint does not exist
                }

                if (! Schema::hasColumn('cart_items', 'variation_id')) {
                    $table->foreignId('variation_id')->nullable()->after('product_id')->constrained('product_variations')->nullOnDelete();
                }

                try {
                    $table->unique(['cart_id', 'product_id', 'variation_id'], 'cart_items_cart_product_variation_unique');
                } catch (\Throwable $e) {
                    // Ignore if unique index already exists
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_items')) {
            Schema::table('cart_items', function (Blueprint $table) {
                try {
                    $table->dropUnique('cart_items_cart_product_variation_unique');
                } catch (\Throwable $e) {
                    // Ignore if index not found
                }

                if (Schema::hasColumn('cart_items', 'variation_id')) {
                    $table->dropForeign(['variation_id']);
                    $table->dropColumn('variation_id');
                }

                try {
                    $table->unique(['cart_id', 'product_id']);
                } catch (\Throwable $e) {
                    // Ignore if unique constraint fails to restore
                }
            });
        }
    }
};
