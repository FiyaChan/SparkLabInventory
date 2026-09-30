<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::where('email', 'admin@inventory-system.test')->first();

        $category = Category::create([
            'name' => 'Solar Science',
            'slug' => 'solar-science',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Solar Rover Kit',
            'slug' => 'solar-rover-kit',
            'sku' => 'SOL-001',
            'price' => 60.00,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $this->product->id,
            'quantity_on_hand' => 20,
            'reorder_level' => 5,
        ]);
    }

    public function test_stock_in_increases_stock_by_quantity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.movement', $this->product), [
                'type' => 'stock_in',
                'quantity' => 15,
                'reason' => 'PO #1001 Restock',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Stock movement recorded successfully.');

        $this->product->inventory->refresh();
        $this->assertEquals(35, $this->product->inventory->quantity_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'stock_in',
            'quantity' => 15,
            'reason' => 'PO #1001 Restock',
        ]);
    }

    public function test_stock_out_decreases_stock_by_quantity(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.movement', $this->product), [
                'type' => 'stock_out',
                'quantity' => 5,
                'reason' => 'Damaged during shipping',
            ]);

        $response->assertRedirect();
        $this->product->inventory->refresh();
        $this->assertEquals(15, $this->product->inventory->quantity_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'stock_out',
            'quantity' => -5,
        ]);
    }

    public function test_stock_adjustment_sets_exact_stock_amount_upwards(): void
    {
        // Current is 20, we adjust to 50
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.movement', $this->product), [
                'type' => 'adjustment',
                'quantity' => 50,
                'reason' => 'Q3 Physical Warehouse Audit',
            ]);

        $response->assertRedirect();
        $this->product->inventory->refresh();
        $this->assertEquals(50, $this->product->inventory->quantity_on_hand);

        // Delta is +30 (50 - 20)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => 30,
            'reason' => 'Q3 Physical Warehouse Audit',
        ]);
    }

    public function test_stock_adjustment_sets_exact_stock_amount_downwards(): void
    {
        // Current is 20, we adjust to 8
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.movement', $this->product), [
                'type' => 'adjustment',
                'quantity' => 8,
                'reason' => 'Audit shrinkage correction',
            ]);

        $response->assertRedirect();
        $this->product->inventory->refresh();
        $this->assertEquals(8, $this->product->inventory->quantity_on_hand);

        // Delta is -12 (8 - 20)
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => -12,
            'reason' => 'Audit shrinkage correction',
        ]);
    }

    public function test_stock_adjustment_can_set_stock_to_zero(): void
    {
        // Current is 20, adjust to 0
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.movement', $this->product), [
                'type' => 'adjustment',
                'quantity' => 0,
                'reason' => 'Complete write-off',
            ]);

        $response->assertRedirect();
        $this->product->inventory->refresh();
        $this->assertEquals(0, $this->product->inventory->quantity_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'type' => 'adjustment',
            'quantity' => -20,
        ]);
    }
}
