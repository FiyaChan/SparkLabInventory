<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('e_invoices')) {
            Schema::create('e_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('invoice_number')->unique(); // e.g. INV-2026-00001, POS-INV-2026-00001
                $table->string('irbm_unique_id')->unique(); // LHDN UUID e.g. IRBM-202609-XYZ123
                $table->string('submission_uid')->nullable();
                $table->string('invoice_type', 20)->default('01'); // 01: Standard Invoice, 02: Credit Note, 03: Debit Note, consolidated: Consolidated
                $table->string('source', 20)->default('pos'); // pos, ecommerce, manual
                $table->string('status', 20)->default('valid'); // valid, pending, submitted, cancelled, invalid
                
                // Supplier (Merchant) Details
                $table->string('supplier_tin');
                $table->string('supplier_name');
                $table->string('supplier_id_type')->default('BRN'); // BRN, NRIC, PASSPORT
                $table->string('supplier_id_value');
                $table->string('supplier_msic_code', 10)->default('47630');
                $table->string('supplier_msic_desc')->nullable();
                $table->string('supplier_sst_no')->nullable();
                $table->string('supplier_email')->nullable();
                $table->string('supplier_phone')->nullable();
                $table->text('supplier_address')->nullable();

                // Buyer (Customer) Details
                $table->string('buyer_tin')->default('EI00000000020'); // EI00000000020 for General Public
                $table->string('buyer_name');
                $table->string('buyer_id_type')->default('NRIC'); // NRIC, BRN, PASSPORT, ARMY, GENERAL_PUBLIC
                $table->string('buyer_id_value')->default('000000000000');
                $table->string('buyer_sst_no')->nullable();
                $table->string('buyer_email')->nullable();
                $table->string('buyer_phone')->nullable();
                $table->text('buyer_address')->nullable();

                // Financials & Tax Amounts
                $table->string('currency_code', 3)->default('MYR');
                $table->decimal('subtotal_amount', 12, 2)->default(0.00);
                $table->decimal('discount_amount', 12, 2)->default(0.00);
                $table->decimal('tax_rate', 5, 2)->default(0.00); // 0.00% or 6.00% / 8.00%
                $table->string('tax_type_code', 10)->default('06'); // 01: Sales Tax, 02: Service Tax, 06: Not Applicable / Exempt
                $table->decimal('tax_amount', 12, 2)->default(0.00);
                $table->decimal('total_payable_amount', 12, 2);

                // Verification & Digital Validation
                $table->string('document_hash')->nullable(); // SHA256 of UBL JSON
                $table->text('qr_code_url'); // Verification URL (https://... or verify route)
                $table->longText('ubl_payload')->nullable(); // Full UBL 2.1 JSON Payload
                $table->text('lhdn_response')->nullable();
                $table->timestamp('issued_at')->useCurrent();
                $table->timestamp('validated_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'source']);
                $table->index('issued_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoices');
    }
};
