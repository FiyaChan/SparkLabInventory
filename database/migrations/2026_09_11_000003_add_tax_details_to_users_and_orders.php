<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tin')) {
                $table->string('tin', 30)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'id_type')) {
                $table->string('id_type', 20)->nullable()->default('NRIC')->after('tin'); // NRIC, BRN, PASSPORT, ARMY
            }
            if (! Schema::hasColumn('users', 'id_number')) {
                $table->string('id_number', 30)->nullable()->after('id_type');
            }
            if (! Schema::hasColumn('users', 'sst_number')) {
                $table->string('sst_number', 30)->nullable()->after('id_number');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'buyer_tin')) {
                $table->string('buyer_tin', 30)->nullable()->after('shipping_address');
            }
            if (! Schema::hasColumn('orders', 'buyer_id_type')) {
                $table->string('buyer_id_type', 20)->nullable()->default('NRIC')->after('buyer_tin');
            }
            if (! Schema::hasColumn('orders', 'buyer_id_number')) {
                $table->string('buyer_id_number', 30)->nullable()->after('buyer_id_type');
            }
            if (! Schema::hasColumn('orders', 'buyer_sst_no')) {
                $table->string('buyer_sst_no', 30)->nullable()->after('buyer_id_number');
            }
            if (! Schema::hasColumn('orders', 'require_einvoice')) {
                $table->boolean('require_einvoice')->default(false)->after('buyer_sst_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tin', 'id_type', 'id_number', 'sst_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['buyer_tin', 'buyer_id_type', 'buyer_id_number', 'buyer_sst_no', 'require_einvoice']);
        });
    }
};
