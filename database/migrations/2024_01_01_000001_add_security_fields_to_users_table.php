<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // MFA support (Google2FA / TOTP)
            $table->string('mfa_secret')->nullable()->after('password');
            $table->boolean('mfa_enabled')->default(false)->after('mfa_secret');

            // Account status — lets admin disable a compromised/abusive account
            // without deleting their order history (referential integrity).
            $table->boolean('is_active')->default(true)->after('mfa_enabled');

            // Track failed logins for extra visibility (in addition to rate limiting)
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_enabled', 'is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
