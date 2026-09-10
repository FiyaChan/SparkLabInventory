<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // nullOnDelete: keep the log entry even if the user account is later deleted —
            // logs must survive account deletion for forensic/audit purposes.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action'); // e.g. "product.deleted", "login.failed", "role.assigned"
            $table->string('ip_address', 45)->nullable(); // 45 chars fits IPv6
            $table->text('user_agent')->nullable();

            // JSON blob for context: old/new values, affected record id, etc.
            $table->json('description')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
