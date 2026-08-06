<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class PriceListDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'عام'],
            ['description' => 'تصنيف تجريبي للمنتجات']
        );

        $unitPiece = Unit::firstOrCreate(
            ['name' => 'قطعة'],
            ['short_name' => 'Pc', 'description' => 'وحدة تجريبية', 'is_active' => true]
        );

        $unitBox = Unit::firstOrCreate(
            ['name' => 'علبة'],
            ['short_name' => 'Bx', 'description' => 'وحدة تجريبية', 'is_active' => true]
        );

        Product::firstOrCreate(
            ['code' => 'PRD-001'],
            [
                'name' => 'منتج تجريبي 1',
                'barcode' => '100001',
                'category_id' => $category->id,
                'unit_id' => $unitPiece->id,
                'purchase_price' => 10,
                'sale_price' => 15,
                'min_stock' => 5,
                'tax_rate' => 0,
                'description' => 'منتج تجريبي لعرض القوائم',
                'is_active' => true,
            ]
        );

        Product::firstOrCreate(
            ['code' => 'PRD-002'],
            [
                'name' => 'منتج تجريبي 2',
                'barcode' => '100002',
                'category_id' => $category->id,
                'unit_id' => $unitBox->id,
                'purchase_price' => 25,
                'sale_price' => 40,
                'min_stock' => 3,
                'tax_rate' => 0,
                'description' => 'منتج تجريبي ثانٍ',
                'is_active' => true,
            ]
        );

        PriceList::firstOrCreate(
            ['name' => 'القائمة الأساسية'],
            ['description' => 'قائمة أسعار تجريبية', 'is_default' => true]
        );
    }
}
