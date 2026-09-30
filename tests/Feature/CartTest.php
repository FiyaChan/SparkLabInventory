<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Product $product;
    protected ProductVariation $variation1;
    protected ProductVariation $variation2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $category = Category::create([
            'name' => 'Physics Kits',
            'slug' => 'physics-kits',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Robotics Science Kit',
            'slug' => 'robotics-science-kit',
            'sku' => 'ROB-001',
            'price' => 50.00,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $this->product->id,
            'quantity_on_hand' => 100,
            'low_stock_threshold' => 10,
        ]);

        $this->variation1 = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Standard Edition',
            'sku' => 'ROB-001-STD',
            'price' => 50.00,
            'stock' => 20,
            'is_active' => true,
        ]);

        $this->variation2 = ProductVariation::create([
            'product_id' => $this->product->id,
            'name' => 'Deluxe Edition',
            'sku' => 'ROB-001-DLX',
            'price' => 75.00,
            'stock' => 15,
            'is_active' => true,
        ]);
    }

    public function test_customer_can_add_product_variation_to_cart(): void
    {
        $response = $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation1->id,
                'quantity' => 2,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'variation_id' => $this->variation1->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_can_add_multiple_different_variations_of_same_product(): void
    {
        // Add variation 1
        $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation1->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        // Add variation 2 of the same product
        $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation2->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'variation_id' => $this->variation1->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'variation_id' => $this->variation2->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_can_view_cart_with_variations(): void
    {
        $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation2->id,
                'quantity' => 1,
            ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('cart.index'));

        $response->assertStatus(200)
            ->assertSee('Robotics Science Kit')
            ->assertSee('Deluxe Edition');
    }

    public function test_customer_can_update_cart_item_quantity(): void
    {
        $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation1->id,
                'quantity' => 2,
            ]);

        $item = $this->customerUser->cart->items()->first();

        $response = $this->actingAs($this->customerUser)
            ->patch(route('cart.update', $item->id), [
                'quantity' => 5,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Cart updated.');

        $this->assertEquals(5, $item->fresh()->quantity);
    }

    public function test_customer_can_remove_item_from_cart(): void
    {
        $this->actingAs($this->customerUser)
            ->post(route('cart.store'), [
                'product_id' => $this->product->id,
                'variation_id' => $this->variation1->id,
                'quantity' => 2,
            ]);

        $item = $this->customerUser->cart->items()->first();
        $this->assertNotNull($item);

        $response = $this->actingAs($this->customerUser)
            ->delete(route('cart.destroy', $item->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Item removed from cart.');

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    }
}
