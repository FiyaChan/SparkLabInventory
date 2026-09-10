<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Seeder;

class ProductVariationSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Category::firstOrCreate(
            ['name' => 'Chemistry & Earth Science'],
            ['slug' => 'chemistry-earth-science']
        );

        $product = Product::firstOrCreate(
            ['sku' => 'SCI-VOLC-01'],
            [
                'category_id' => $cat->id,
                'name' => 'Volcano Eruption Science Kit',
                'slug' => 'volcano-eruption-science-kit',
                'description' => 'Hands-on erupting volcano science experiment kit.',
                'price' => 35.00,
                'cost_price' => 15.00,
                'is_active' => true,
            ]
        );

        Inventory::firstOrCreate(
            ['product_id' => $product->id],
            ['quantity_on_hand' => 25, 'reorder_level' => 5]
        );

        ProductVariation::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Standard Edition'],
            [
                'sku' => 'SCI-VOLC-STD',
                'price' => 35.00,
                'cost_price' => 15.00,
                'stock' => 10,
                'is_active' => true,
            ]
        );

        ProductVariation::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Deluxe Kit (+ Safety Goggles & Lava Dye)'],
            [
                'sku' => 'SCI-VOLC-DLX',
                'price' => 49.90,
                'cost_price' => 22.00,
                'stock' => 8,
                'is_active' => true,
            ]
        );

        ProductVariation::firstOrCreate(
            ['product_id' => $product->id, 'name' => 'Mega Classroom Pack (5x Sets)'],
            [
                'sku' => 'SCI-VOLC-MGA',
                'price' => 120.00,
                'cost_price' => 55.00,
                'stock' => 5,
                'is_active' => true,
            ]
        );
    }
}
