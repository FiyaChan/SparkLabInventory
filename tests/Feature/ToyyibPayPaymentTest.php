<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ToyyibPayPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        config([
            'toyyibpay.user_secret_key' => 'mock-user-secret-key',
            'toyyibpay.category_code'   => 'mock-cat-code',
            'toyyibpay.sandbox'         => true,
            'toyyibpay.sandbox_url'     => 'https://dev.toyyibpay.com',
        ]);

        $this->customer = User::factory()->create([
            'name'  => 'Nur Aisha',
            'email' => 'aisha@example.com',
        ]);
        $this->customer->assignRole('customer');

        $category = Category::create([
            'name' => 'Physics Kits',
            'slug' => 'physics-kits',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name'        => 'Solar Robot Kit',
            'slug'        => 'solar-robot-kit',
            'sku'         => 'SCI-ROBOT-01',
            'description' => 'Solar kit for kids',
            'price'       => 50.00,
            'cost_price'  => 25.00,
            'is_active'   => true,
        ]);

        Inventory::create([
            'product_id'       => $this->product->id,
            'quantity_on_hand' => 10,
            'reorder_level'    => 2,
        ]);
    }

    public function test_customer_can_checkout_with_toyyibpay_and_is_redirected_to_payment_page()
    {
        Http::fake([
            'https://dev.toyyibpay.com/index.php/api/createBill' => Http::response([
                ['BillCode' => 'tb_mock_bill_123'],
            ], 200),
        ]);

        // Add item to cart
        $cart = Cart::create(['user_id' => $this->customer->id]);
        $cart->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        $response = $this->actingAs($this->customer)->post(route('checkout.store'), [
            'shipping_name'    => 'Nur Aisha',
            'shipping_phone'   => '+60123456789',
            'shipping_address' => 'No 12, Jalan Sains, 43000 Kajang, Selangor',
            'payment_method'   => 'toyyibpay',
        ]);

        $response->assertRedirect('https://dev.toyyibpay.com/tb_mock_bill_123');

        $this->assertDatabaseHas('orders', [
            'user_id'       => $this->customer->id,
            'status'        => 'pending',
            'total_amount'  => 100.00,
            'shipping_name' => 'Nur Aisha',
        ]);

        $order = Order::where('user_id', $this->customer->id)->first();

        $this->assertDatabaseHas('payments', [
            'order_id'        => $order->id,
            'method'          => 'toyyibpay',
            'status'          => 'pending',
            'transaction_ref' => 'tb_mock_bill_123',
            'amount'          => 100.00,
        ]);
    }

    public function test_toyyibpay_return_handler_processes_successful_payment()
    {
        Http::fake([
            'https://dev.toyyibpay.com/index.php/api/getBillTransactions' => Http::response([
                [
                    'billpaymentStatus'    => '1',
                    'billpaymentInvoiceNo' => 'TP-INV-999',
                    'billpaymentAmount'    => '100.00',
                ],
            ], 200),
        ]);

        $order = Order::create([
            'user_id'          => $this->customer->id,
            'order_number'     => 'ORD-2026-00001',
            'status'           => 'pending',
            'total_amount'     => 100.00,
            'shipping_name'    => 'Nur Aisha',
            'shipping_phone'   => '+60123456789',
            'shipping_address' => 'Kajang',
        ]);

        Payment::create([
            'order_id'        => $order->id,
            'method'          => 'toyyibpay',
            'status'          => 'pending',
            'transaction_ref' => 'tb_bill_success_123',
            'amount'          => 100.00,
        ]);

        $response = $this->actingAs($this->customer)->get(route('toyyibpay.return', [
            'status_id'      => '1',
            'billcode'       => 'tb_bill_success_123',
            'order_id'       => $order->order_number,
            'transaction_id' => 'TP-INV-999',
        ]));

        $response->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id'        => $order->id,
            'status'          => 'paid',
            'transaction_ref' => 'tb_bill_success_123',
        ]);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_toyyibpay_return_handler_handles_failed_payment()
    {
        Http::fake([
            'https://dev.toyyibpay.com/index.php/api/getBillTransactions' => Http::response([], 200),
        ]);

        $order = Order::create([
            'user_id'          => $this->customer->id,
            'order_number'     => 'ORD-2026-00002',
            'status'           => 'pending',
            'total_amount'     => 100.00,
            'shipping_name'    => 'Nur Aisha',
            'shipping_phone'   => '+60123456789',
            'shipping_address' => 'Kajang',
        ]);

        Payment::create([
            'order_id'        => $order->id,
            'method'          => 'toyyibpay',
            'status'          => 'pending',
            'transaction_ref' => 'tb_bill_failed_123',
            'amount'          => 100.00,
        ]);

        $response = $this->actingAs($this->customer)->get(route('toyyibpay.return', [
            'status_id' => '3',
            'billcode'  => 'tb_bill_failed_123',
            'order_id'  => $order->order_number,
        ]));

        $response->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status'   => 'failed',
        ]);
    }

    public function test_toyyibpay_server_to_server_webhook_callback()
    {
        $order = Order::create([
            'user_id'          => $this->customer->id,
            'order_number'     => 'ORD-2026-00003',
            'status'           => 'pending',
            'total_amount'     => 100.00,
            'shipping_name'    => 'Nur Aisha',
            'shipping_phone'   => '+60123456789',
            'shipping_address' => 'Kajang',
        ]);

        Payment::create([
            'order_id'        => $order->id,
            'method'          => 'toyyibpay',
            'status'          => 'pending',
            'transaction_ref' => 'tb_bill_webhook_123',
            'amount'          => 100.00,
        ]);

        // Server-to-server callback POST
        $response = $this->post(route('toyyibpay.callback'), [
            'refno'    => 'TP-WEBHOOK-888',
            'status'   => '1',
            'reason'   => 'SUCCESS',
            'billcode' => 'tb_bill_webhook_123',
            'order_id' => $order->order_number,
            'amount'   => '100.00',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status'   => 'paid',
        ]);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_customer_can_retry_pending_payment()
    {
        Http::fake([
            'https://dev.toyyibpay.com/index.php/api/createBill' => Http::response([
                ['BillCode' => 'tb_retry_bill_456'],
            ], 200),
        ]);

        $order = Order::create([
            'user_id'          => $this->customer->id,
            'order_number'     => 'ORD-2026-00004',
            'status'           => 'pending',
            'total_amount'     => 100.00,
            'shipping_name'    => 'Nur Aisha',
            'shipping_phone'   => '+60123456789',
            'shipping_address' => 'Kajang',
        ]);

        Payment::create([
            'order_id'        => $order->id,
            'method'          => 'toyyibpay',
            'status'          => 'failed',
            'transaction_ref' => 'old_failed_bill',
            'amount'          => 100.00,
        ]);

        $response = $this->actingAs($this->customer)->get(route('orders.pay', $order));

        $response->assertRedirect('https://dev.toyyibpay.com/tb_retry_bill_456');

        $this->assertDatabaseHas('payments', [
            'order_id'        => $order->id,
            'transaction_ref' => 'tb_retry_bill_456',
        ]);
    }
}
