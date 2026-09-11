<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('e_invoice_settings')) {
            Schema::create('e_invoice_settings', function (Blueprint $table) {
                $table->id();
                $table->string('company_name')->default('SparkLab Kids Science');
                $table->string('company_tin')->default('C25890123040');
                $table->string('company_reg_no')->default('202601009988');
                $table->string('company_sst_no')->nullable()->default('W10-2401-32000001');
                $table->string('msic_code', 10)->default('47630');
                $table->string('msic_description')->default('Retail sale of games, toys and educational scientific laboratory equipment in specialized stores');
                $table->string('business_activity')->default('Retail sale of educational kits and laboratory equipment');
                $table->string('contact_email')->default('einvoice@sparklab.my');
                $table->string('contact_phone')->default('+60 3-8888 1234');
                $table->string('address_line1')->default('Lot 4.12, Discovery Mall, Jalan Ilmu');
                $table->string('address_line2')->nullable()->default('Presint 5');
                $table->string('postal_code', 10)->default('62000');
                $table->string('city')->default('Putrajaya');
                $table->string('state')->default('Wilayah Persekutuan Putrajaya');
                $table->string('country', 3)->default('MYS');

                // LHDN Integration Mode & Credentials
                $table->string('mode', 20)->default('simulation'); // simulation, sandbox, production
                $table->string('lhdn_client_id')->nullable();
                $table->string('lhdn_client_secret')->nullable();
                $table->boolean('auto_generate_on_payment')->default(true);
                $table->decimal('default_tax_rate', 5, 2)->default(0.00);
                $table->string('default_tax_type_code', 10)->default('06'); // 06: Not Applicable / Exempt, 01: Sales Tax

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_settings');
    }
};
