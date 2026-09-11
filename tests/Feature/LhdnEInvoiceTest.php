<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EInvoice;
use App\Models\EInvoiceSetting;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\EInvoiceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LhdnEInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::where('email', 'admin@inventory-system.test')->first();

        $this->customerUser = User::factory()->create([
            'name' => 'Ahmad Daniel',
            'email' => 'ahmad@example.com',
            'tin' => 'IG98765432010',
            'id_type' => 'NRIC',
            'id_number' => '950412-10-5544',
        ]);
        $this->customerUser->assignRole('customer');

        $category = Category::create([
            'name' => 'Chemistry Kits',
            'slug' => 'chemistry-kits',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Crystal Growing Laboratory Kit',
            'slug' => 'crystal-growing-kit',
            'sku' => 'SCI-CHEM-01',
            'description' => 'Science experiment kit.',
            'price' => 50.00,
            'cost_price' => 25.00,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $this->product->id,
            'quantity_on_hand' => 20,
            'reorder_level' => 5,
        ]);
    }

    public function test_pos_checkout_automatically_generates_lhdn_einvoice(): void
    {
        $payload = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'cash',
            'tendered_amount' => 100.00,
            'discount_type' => 'fixed',
            'discount_value' => 0.00,
            'customer_name' => 'Walk-in Scientist',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('admin.pos.checkout'), $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $orderId = $response->json('order.id');
        $this->assertDatabaseHas('e_invoices', [
            'order_id' => $orderId,
            'status' => 'valid',
            'source' => 'pos',
            'supplier_tin' => 'C25890123040',
            'buyer_tin' => 'EI00000000020', // Default walk-in General Public TIN
            'total_payable_amount' => 100.00,
        ]);

        $eInvoice = EInvoice::where('order_id', $orderId)->first();
        $this->assertNotNull($eInvoice);
        $this->assertStringStartsWith('IRBM-', $eInvoice->irbm_unique_id);
        $this->assertNotEmpty($eInvoice->ubl_payload);
        $this->assertNotEmpty($eInvoice->qr_code_url);
    }

    public function test_pos_receipt_view_renders_lhdn_qr_code_and_uuid(): void
    {
        $payload = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'cash',
            'tendered_amount' => 50.00,
        ];

        $checkoutResponse = $this->actingAs($this->adminUser)
            ->postJson(route('admin.pos.checkout'), $payload);

        $orderId = $checkoutResponse->json('order.id');

        $receiptResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.pos.receipt', $orderId));

        $receiptResponse->assertStatus(200)
            ->assertSee('LHDN MyInvois Digital Validation')
            ->assertSee('Supplier TIN:')
            ->assertSee('IRBM-');
    }

    public function test_ecommerce_checkout_creates_order_with_tax_profile_and_einvoice(): void
    {
        // Add to cart
        $this->actingAs($this->customerUser)
            ->post(route('cart.add'), [
                'product_id' => $this->product->id,
                'quantity' => 1,
            ]);

        // Place checkout with e-invoice option
        $checkoutResponse = $this->actingAs($this->customerUser)
            ->post(route('checkout.store'), [
                'shipping_name' => 'Ahmad Daniel',
                'shipping_phone' => '+60123456789',
                'shipping_address' => 'No 12, Jalan Ilmu 3, Cyberjaya, Selangor',
                'payment_method' => 'online_simulation',
                'require_einvoice' => 1,
                'buyer_tin' => 'IG98765432010',
                'buyer_id_type' => 'NRIC',
                'buyer_id_number' => '950412-10-5544',
            ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('IG98765432010', $order->buyer_tin);
        $this->assertEquals('950412-10-5544', $order->buyer_id_number);

        // Verify e-Invoice is created
        $this->assertDatabaseHas('e_invoices', [
            'order_id' => $order->id,
            'source' => 'ecommerce',
            'status' => 'valid',
            'buyer_tin' => 'IG98765432010',
            'total_payable_amount' => 50.00,
        ]);
    }

    public function test_public_qr_code_verification_url_displays_verified_document(): void
    {
        $eInvoiceService = app(EInvoiceService::class);
        $order = Order::create([
            'user_id' => $this->customerUser->id,
            'order_number' => 'ORD-TEST-001',
            'status' => 'completed',
            'source' => 'web',
            'total_amount' => 50.00,
            'shipping_name' => 'Ahmad Daniel',
            'shipping_phone' => '+60123456789',
            'shipping_address' => 'Cyberjaya',
            'buyer_tin' => 'IG98765432010',
        ]);

        $eInvoice = $eInvoiceService->generateForOrder($order);

        $response = $this->get(route('einvoice.verify', $eInvoice->irbm_unique_id));

        $response->assertStatus(200)
            ->assertSee('LHDN MyInvois Validation Portal')
            ->assertSee('Valid &amp; Verified Document', false)
            ->assertSee($eInvoice->irbm_unique_id)
            ->assertSee('IG98765432010');
    }

    public function test_admin_can_view_einvoices_list_and_update_settings(): void
    {
        // Admin index
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.einvoices.index'));

        $response->assertStatus(200)
            ->assertSee('LHDN e-Invoicing Management');

        // Admin update settings
        $settingsResponse = $this->actingAs($this->adminUser)
            ->put(route('admin.einvoices.settings.update'), [
                'company_name' => 'SparkLab Kids Science HQ Sdn Bhd',
                'company_tin' => 'C99998888010',
                'company_reg_no' => '202601009988',
                'company_sst_no' => 'W10-2401-32000001',
                'msic_code' => '47630',
                'msic_description' => 'Retail sale of science kits and educational toys',
                'business_activity' => 'Retail',
                'contact_email' => 'tax@sparklab.my',
                'contact_phone' => '+60 3-8888 9999',
                'address_line1' => 'Lot 10, Discovery Mall',
                'postal_code' => '62000',
                'city' => 'Putrajaya',
                'state' => 'Putrajaya',
                'country' => 'MYS',
                'mode' => 'simulation',
                'auto_generate_on_payment' => 1,
                'default_tax_rate' => 0.00,
                'default_tax_type_code' => '06',
            ]);

        $settingsResponse->assertRedirect();
        $this->assertDatabaseHas('e_invoice_settings', [
            'company_name' => 'SparkLab Kids Science HQ Sdn Bhd',
            'company_tin' => 'C99998888010',
        ]);
    }

    public function test_consolidated_einvoice_batch_generation(): void
    {
        // Create 2 completed POS orders without e-invoice
        Order::create([
            'user_id' => $this->customerUser->id,
            'order_number' => 'POS-BATCH-001',
            'status' => 'completed',
            'source' => 'pos',
            'total_amount' => 30.00,
            'shipping_name' => 'Walk-in 1',
            'shipping_phone' => 'N/A',
            'shipping_address' => 'POS',
        ]);

        Order::create([
            'user_id' => $this->customerUser->id,
            'order_number' => 'POS-BATCH-002',
            'status' => 'completed',
            'source' => 'pos',
            'total_amount' => 70.00,
            'shipping_name' => 'Walk-in 2',
            'shipping_phone' => 'N/A',
            'shipping_address' => 'POS',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.einvoices.consolidated.generate'), [
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('e_invoices', [
            'invoice_type' => 'consolidated',
            'buyer_tin' => 'EI00000000020',
            'status' => 'valid',
        ]);
    }
}
