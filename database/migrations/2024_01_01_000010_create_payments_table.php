<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->enum('method', ['cod', 'online_simulation']);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

            // Simulated gateway reference number — in a real system this would come
            // from Stripe/PayPal's API response, never generated client-side.
            $table->string('transaction_ref')->nullable()->unique();

            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
