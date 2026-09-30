<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
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
            'name' => 'Chemistry Sets',
            'slug' => 'chemistry-sets',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_product_without_manual_sku_and_slug(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.products.store'), [
                'category_id' => $this->category->id,
                'name' => 'Volcano Chemistry Experiment Kit',
                'description' => 'A complete erupting volcano experiment set.',
                'price' => '45.00',
                'cost_price' => '20.00',
                'initial_quantity' => 50,
                'reorder_level' => 10,
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'name' => 'Volcano Chemistry Experiment Kit',
            'category_id' => $this->category->id,
            'price' => 45.00,
            'cost_price' => 20.00,
            'is_active' => true,
        ]);

        $product = Product::where('name', 'Volcano Chemistry Experiment Kit')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('volcano-chemistry-experiment-kit-', $product->slug);
        $this->assertStringStartsWith('SKU-', $product->sku);
        $this->assertEquals(50, $product->inventory->quantity_on_hand);
    }

    public function test_admin_can_create_product_with_variations(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.products.store'), [
                'category_id' => $this->category->id,
                'name' => 'Microscope Kit Deluxe',
                'sku' => 'MIC-KIT-001',
                'price' => '100.00',
                'cost_price' => '50.00',
                'initial_quantity' => 10,
                'is_active' => '1',
                'variations' => [
                    [
                        'name' => 'Basic Pack',
                        'price' => '100.00',
                        'stock' => 5,
                    ],
                    [
                        'name' => 'Advanced Pack with Prepared Slides',
                        'price' => '130.00',
                        'stock' => 5,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'MIC-KIT-001')->first();
        $this->assertNotNull($product);
        $this->assertCount(2, $product->variations);
    }
}
