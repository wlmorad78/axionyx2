<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\ItemPrice;
use App\Models\ItemBarcode;
use App\Models\PriceList;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class SouqatCoinProductsSeeder extends Seeder
{
    public function run(): void
    {
        $units = $this->seedUnits();
        $companies = Company::all();

        foreach ($companies as $company) {
            $category = ItemCategory::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-SOQ'],
                ['name_ar' => 'سوفت كوين', 'name_en' => 'Souqat Coin', 'is_active' => true]
            );

            $priceList = PriceList::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'PL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
                ['name_ar' => 'القائمة الأساسية', 'name_en' => 'Main Price List', 'is_default' => true, 'is_active' => true]
            );

            $albaUnit = Unit::where('code', 'ALBA')->first();
            $khatootaUnit = Unit::where('code', 'KHAToota')->first();
            $cartonUnit = Unit::where('code', 'CARTON')->first();

            $products = [
                [
                    'code' => 'ITM-SOQ-001',
                    'name_ar' => 'بوكس ابيض',
                    'name_en' => 'White Box',
                    'short_name' => 'ابيض',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
                [
                    'code' => 'ITM-SOQ-002',
                    'name_ar' => 'بوكس راوند',
                    'name_en' => 'Round Box',
                    'short_name' => 'راوند',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
                [
                    'code' => 'ITM-SOQ-003',
                    'name_ar' => 'سوبر',
                    'name_en' => 'Super',
                    'short_name' => 'سوبر',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
                [
                    'code' => 'ITM-SOQ-004',
                    'name_ar' => 'مونديال احمر',
                    'name_en' => 'Red Mondial',
                    'short_name' => 'احمر',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
                [
                    'code' => 'ITM-SOQ-005',
                    'name_ar' => 'مونديال ازرق',
                    'name_en' => 'Blue Mondial',
                    'short_name' => 'ازرق',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
                [
                    'code' => 'ITM-SOQ-006',
                    'name_ar' => 'مونديال سلفر',
                    'name_en' => 'Silver Mondial',
                    'short_name' => 'سلفر',
                    'alba_purchase' => 47.85,
                    'alba_sale' => 47.95,
                    'khatoota_purchase' => 478.50,
                    'khatoota_sale' => 479.50,
                    'carton_purchase' => 23925.00,
                    'carton_sale' => 23975.00,
                ],
            ];

            foreach ($products as $index => $productData) {
                $uniqueCode = $productData['code'] . '-' . str_pad($company->id, 2, '0', STR_PAD_LEFT);
                $item = Item::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $uniqueCode],
                    [
                        'item_category_id' => $category->id,
                        'name_ar' => $productData['name_ar'],
                        'name_en' => $productData['name_en'],
                        'short_name' => $productData['short_name'],
                        'barcode' => '622' . str_pad($company->id * 10000 + 9000 + $index, 9, '0', STR_PAD_LEFT),
                        'base_unit_id' => $albaUnit?->id,
                        'minimum_stock' => 10,
                        'maximum_stock' => 500,
                        'reorder_quantity' => 50,
                        'is_active' => true,
                        'is_taxable' => false,
                    ]
                );

                // علبة (Box) - الوحدة الأساسية
                if ($albaUnit) {
                    ItemUnit::updateOrCreate(
                        ['item_id' => $item->id, 'unit_id' => $albaUnit->id],
                        [
                            'conversion_factor' => 1,
                            'is_default' => true,
                            'is_purchase_unit' => true,
                            'is_sales_unit' => true,
                            'purchase_price' => $productData['alba_purchase'],
                            'sale_price' => $productData['alba_sale'],
                        ]
                    );

                    ItemPrice::updateOrCreate(
                        ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $albaUnit->id],
                        ['price' => $productData['alba_sale'], 'is_active' => true]
                    );
                }

                // خطوطة (Packet) - 10 علبة
                if ($khatootaUnit) {
                    ItemUnit::updateOrCreate(
                        ['item_id' => $item->id, 'unit_id' => $khatootaUnit->id],
                        [
                            'conversion_factor' => 10,
                            'is_default' => false,
                            'is_purchase_unit' => true,
                            'is_sales_unit' => true,
                            'purchase_price' => $productData['khatoota_purchase'],
                            'sale_price' => $productData['khatoota_sale'],
                        ]
                    );

                    ItemPrice::updateOrCreate(
                        ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $khatootaUnit->id],
                        ['price' => $productData['khatoota_sale'], 'is_active' => true]
                    );
                }

                // كرتونة (Carton) - 500 علبة
                if ($cartonUnit) {
                    ItemUnit::updateOrCreate(
                        ['item_id' => $item->id, 'unit_id' => $cartonUnit->id],
                        [
                            'conversion_factor' => 500,
                            'is_default' => false,
                            'is_purchase_unit' => true,
                            'is_sales_unit' => false,
                            'purchase_price' => $productData['carton_purchase'],
                            'sale_price' => $productData['carton_sale'],
                        ]
                    );

                    ItemPrice::updateOrCreate(
                        ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $cartonUnit->id],
                        ['price' => $productData['carton_sale'], 'is_active' => true]
                    );
                }

                // Barcode
                ItemBarcode::updateOrCreate(
                    ['item_id' => $item->id, 'barcode' => '622' . str_pad($company->id * 10000 + 9000 + $index, 9, '0', STR_PAD_LEFT)],
                    ['unit_id' => $albaUnit?->id, 'is_default' => true]
                );
            }

            $this->command->info("✅ تم إضافة 6 منتجات سوفت كوين للشركة: {$company->name_ar}");
        }
    }

    private function seedUnits(): array
    {
        $units = [
            ['code' => 'ALBA', 'name_ar' => 'علبة', 'name_en' => 'Box', 'symbol' => 'box'],
            ['code' => 'KHAToota', 'name_ar' => 'خطوطة', 'name_en' => 'Packet', 'symbol' => 'pkt'],
            ['code' => 'CARTON', 'name_ar' => 'كرتونة', 'name_en' => 'Carton', 'symbol' => 'ctn'],
        ];

        $result = [];
        foreach ($units as $u) {
            $result[] = Unit::updateOrCreate(['code' => $u['code']], $u);
        }
        return $result;
    }
}
