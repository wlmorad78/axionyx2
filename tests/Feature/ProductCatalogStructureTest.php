<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\SubCategory;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_catalog_supports_subcategories_units_and_latest_prices(): void
    {
        $parent = Category::create([
            'name' => 'Electronics',
            'description' => 'Main category',
        ]);

        $child = SubCategory::create([
            'category_id' => $parent->id,
            'name' => 'Mobile Phones',
            'description' => 'Subcategory example',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $unit = Unit::create([
            'name' => 'Piece',
            'short_name' => 'Pc',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Smartphone',
            'category_id' => $parent->id,
            'sub_category_id' => $child->id,
            'unit_id' => $unit->id,
            'purchase_price' => 500,
            'sale_price' => 700,
            'min_stock' => 5,
            'tax_rate' => 15,
            'is_active' => true,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'purchase_price' => 480,
            'sale_price' => 720,
            'effective_from' => now()->subDay(),
            'notes' => 'Latest price update',
        ]);

        $this->assertTrue($child->category()->exists());
        $this->assertTrue($product->category()->exists());
        $this->assertTrue($product->unit()->exists());
        $this->assertSame('110001', $product->code);
        $this->assertTrue($product->latestPrices()->exists());
        $this->assertSame('Piece', $product->unit->name);
        $this->assertEquals(480.0, (float) $product->latestPrices()->latest('effective_from')->first()->purchase_price);
    }
}
