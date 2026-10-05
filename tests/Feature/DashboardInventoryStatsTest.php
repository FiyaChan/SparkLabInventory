<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInventoryStatsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::where('email', 'admin@inventory-system.test')->first();

        $this->category = Category::create([
            'name' => 'Science Kits',
            'slug' => 'science-kits',
            'is_active' => true,
        ]);
    }

    public function test_dashboard_does_not_count_stock_or_alerts_from_deleted_products(): void
    {
        // 1. Create active product with 25 units (in stock, reorder 5)
        $product1 = Product::create([
            'category_id' => $this->category->id,
            'sku' => 'SCI-VOLC-01',
            'name' => 'Volcano Eruption Science Kit',
            'slug' => 'volcano-eruption-science-kit',
            'price' => 35.00,
            'is_active' => true,
        ]);
        Inventory::create([
            'product_id' => $product1->id,
            'quantity_on_hand' => 25,
            'reorder_level' => 5,
        ]);

        // 2. Create another product with 5 units (low stock, reorder 10)
        $product2 = Product::create([
            'category_id' => $this->category->id,
            'sku' => 'TEST-001',
            'name' => 'Test Item',
            'slug' => 'test-item',
            'price' => 20.00,
            'is_active' => true,
        ]);
        Inventory::create([
            'product_id' => $product2->id,
            'quantity_on_hand' => 5,
            'reorder_level' => 10,
        ]);

        // Before deletion: 2 products, 30 units total, 1 low stock alert
        $response = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertViewHas('inventoryOverview', function (array $stats) {
            return $stats['total_products'] === 2
                && $stats['total_units'] === 30
                && $stats['low_stock_count'] === 1
                && $stats['out_of_stock_count'] === 0;
        });

        // 3. Delete product2 (soft delete)
        $product2->delete();

        // After deletion: only 1 active product, 25 units total, 0 low stock alerts
        $responseAfterDelete = $this->actingAs($this->adminUser)->get(route('admin.dashboard'));
        $responseAfterDelete->assertOk();
        $responseAfterDelete->assertViewHas('inventoryOverview', function (array $stats) {
            return $stats['total_products'] === 1
                && $stats['total_units'] === 25
                && $stats['low_stock_count'] === 0
                && $stats['out_of_stock_count'] === 0;
        });
    }
}
