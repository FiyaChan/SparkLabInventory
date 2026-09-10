<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected User $customerUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::where('email', 'admin@inventory-system.test')->first();

        $this->staffUser = User::factory()->create([
            'email' => 'staff@test.com',
        ]);
        $this->staffUser->assignRole('staff');

        $this->customerUser = User::factory()->create([
            'email' => 'customer@test.com',
        ]);
        $this->customerUser->assignRole('customer');

        $category = Category::create([
            'name' => 'Physics Kits',
            'slug' => 'physics-kits',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Solar Robot Experiment Kit',
            'slug' => 'solar-robot-experiment-kit',
            'sku' => 'SCI-ROBOT-01',
            'description' => 'Educational solar robot kit for young engineers.',
            'price' => 45.00,
            'cost_price' => 20.00,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $this->product->id,
            'quantity_on_hand' => 15,
            'reorder_level' => 3,
        ]);
    }

    public function test_pos_terminal_access_is_restricted_by_role(): void
    {
        // Admin allowed
        $response = $this->actingAs($this->adminUser)->get(route('admin.pos.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.pos.terminal');

        // Staff allowed
        $response = $this->actingAs($this->staffUser)->get(route('admin.pos.index'));
        $response->assertStatus(200);

        // Customer forbidden
        $response = $this->actingAs($this->customerUser)->get(route('admin.pos.index'));
        $response->assertStatus(403);
    }

    public function test_pos_live_search_endpoints_return_json(): void
    {
        $response = $this->actingAs($this->staffUser)->getJson(route('admin.pos.search-products', [
            'search' => 'Solar',
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'sku' => 'SCI-ROBOT-01',
            'name' => 'Solar Robot Experiment Kit',
        ]);

        $response = $this->actingAs($this->staffUser)->getJson(route('admin.pos.search-customers', [
            'query' => 'customer',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['customers']);
    }

    public function test_successful_pos_checkout_with_cash_and_stock_deduction(): void
    {
        $initialStock = $this->product->inventory->quantity_on_hand; // 15
        $qtyToBuy = 2;

        $response = $this->actingAs($this->staffUser)->postJson(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $qtyToBuy,
                ],
            ],
            'payment_method' => 'cash',
            'tendered_amount' => 100.00,
            'discount_type' => 'fixed',
            'discount_value' => 5.00, // Subtotal 90.00 - 5.00 = 85.00 total
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Order Record
        $order = Order::where('source', 'pos')->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals(85.00, $order->total_amount);

        // Verify Order Items
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => $qtyToBuy,
            'unit_price' => 45.00,
            'subtotal' => 90.00,
        ]);

        // Verify Payment Record
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cash',
            'status' => 'paid',
            'amount' => 85.00,
        ]);

        // Verify Inventory Deduction
        $this->assertEquals($initialStock - $qtyToBuy, $this->product->fresh()->inventory->quantity_on_hand);

        // Verify Stock Movement Audit Log
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'stock_out',
            'quantity' => -2,
            'performed_by' => $this->staffUser->id,
        ]);

        // Verify Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'pos.order_completed',
        ]);
    }

    public function test_pos_checkout_fails_if_insufficient_stock_and_rolls_back(): void
    {
        $initialStock = $this->product->inventory->quantity_on_hand; // 15

        $response = $this->actingAs($this->staffUser)->postJson(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 999, // exceeds stock
                ],
            ],
            'payment_method' => 'cash',
            'tendered_amount' => 50000.00,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);

        // Verify stock is untouched
        $this->assertEquals($initialStock, $this->product->fresh()->inventory->quantity_on_hand);

        // Verify no order was created
        $this->assertDatabaseMissing('orders', [
            'source' => 'pos',
        ]);
    }

    public function test_pos_receipt_view_and_pdf_download(): void
    {
        $response = $this->actingAs($this->staffUser)->postJson(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'qr',
        ]);

        $orderId = $response->json('order.id');
        $order = Order::findOrFail($orderId);

        // Test HTML Receipt View
        $receiptResponse = $this->actingAs($this->staffUser)->get(route('admin.pos.receipt', $order));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee($order->order_number);

        // Test PDF Download
        $pdfResponse = $this->actingAs($this->staffUser)->get(route('admin.pos.receipt.pdf', $order));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
    }

    public function test_pos_checkout_with_product_variation_deducts_variant_stock(): void
    {
        $variation = \App\Models\ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Deluxe Solar Pack',
            'sku' => 'SCI-ROBOT-01-DLX',
            'price' => 59.00,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staffUser)->postJson(route('admin.pos.checkout'), [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $variation->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'cash',
            'tendered_amount' => 150.00,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check variation stock deduction
        $this->assertEquals(8, $variation->fresh()->stock);

        // Check order item has variation details
        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
            'variation_id' => $variation->id,
            'variant_name' => 'Deluxe Solar Pack',
            'unit_price' => 59.00,
            'quantity' => 2,
        ]);
    }
}
