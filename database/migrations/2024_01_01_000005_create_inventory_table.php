<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();

            // unique() -> enforces exactly one inventory row per product (1:1 relationship)
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('reorder_level')->default(10); // triggers low-stock alert

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
