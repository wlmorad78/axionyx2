<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemBarcode;
use App\Models\ItemCategory;
use App\Models\ItemPrice;
use App\Models\ItemSubCategory;
use App\Models\ItemUnit;
use App\Models\PriceList;
use App\Models\ProductCompany;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductsUnitsPricesSeeder extends Seeder
{
    public function run(): void
    {
        $units = $this->seedUnits();

        $unitAlba = $units['ALBA'];
        $unitKhatoota = $units['KHAToota'];
        $unitCarton = $units['CARTON'];
        $unitUNT001 = $units['UNT-001'];
        $unitUNT002 = $units['UNT-002'];
        $unitUNT003 = $units['UNT-003'];

        $companies = Company::all();

        foreach ($companies as $company) {
            $mfg = ProductCompany::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'PC-' . str_pad($company->id, 4, '0', STR_PAD_LEFT)],
                [
                    'name_ar' => 'الشركة الشرقية',
                    'name_en' => 'Eastern Company',
                    'is_active' => true,
                ]
            );

            $catSigarettes = ItemCategory::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CAT-SIG-' . $company->id],
                [
                    'name_ar' => 'سجائر',
                    'name_en' => 'Cigarettes',
                    'is_active' => true,
                ]
            );

            $catSouqat = ItemCategory::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CAT-SOQ-' . $company->id],
                [
                    'name_ar' => 'سوفت كوين',
                    'name_en' => 'Souqat Coin',
                    'is_active' => true,
                ]
            );

            ItemSubCategory::updateOrCreate(
                ['item_category_id' => $catSigarettes->id, 'code' => 'SC-SIG-' . $company->id],
                [
                    'company_id' => $company->id,
                    'name_ar' => 'سجائر',
                    'name_en' => 'Cigarettes',
                    'is_active' => true,
                ]
            );

            ItemSubCategory::updateOrCreate(
                ['item_category_id' => $catSouqat->id, 'code' => 'SC-SOQ-' . $company->id],
                [
                    'company_id' => $company->id,
                    'name_ar' => 'سوفت كوين',
                    'name_en' => 'Souqat Coin',
                    'is_active' => true,
                ]
            );

            $priceList = PriceList::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'PL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
                [
                    'name_ar' => 'القائمة الأساسية',
                    'name_en' => 'Main Price List',
                    'is_default' => true,
                    'is_active' => true,
                    'is_taxable' => true,
                    'status' => 'ACTIVE',
                ]
            );

            $items = [
                ['code' => 'ITM-00001', 'name_ar' => 'سوفت كوين', 'name_en' => 'Souqat Coin', 'cat' => 'souqat', 'barcode' => null],
                ['code' => 'ITM-00002', 'name_ar' => 'بوكس ابيض', 'name_en' => 'White Box', 'short_name' => 'ابيض', 'cat' => 'souqat', 'barcode' => '622000009000'],
                ['code' => 'ITM-00003', 'name_ar' => 'بوكس راوند', 'name_en' => 'Round Box', 'short_name' => 'راوند', 'cat' => 'souqat', 'barcode' => '622000009001'],
                ['code' => 'ITM-00004', 'name_ar' => 'سوبر', 'name_en' => 'Super', 'short_name' => 'سوبر', 'cat' => 'souqat', 'barcode' => '622000009002'],
                ['code' => 'ITM-00005', 'name_ar' => 'مونديال احمر', 'name_en' => 'Red Mondial', 'short_name' => 'احمر', 'cat' => 'souqat', 'barcode' => '622000009003'],
                ['code' => 'ITM-00006', 'name_ar' => 'مونديال ازرق', 'name_en' => 'Blue Mondial', 'short_name' => 'ازرق', 'cat' => 'souqat', 'barcode' => '622000009004'],
                ['code' => 'ITM-00007', 'name_ar' => 'مونديال سلفر', 'name_en' => 'Silver Mondial', 'short_name' => 'سلفر', 'cat' => 'souqat', 'barcode' => '622000009005'],
                ['code' => 'ITM-00008', 'name_ar' => 'بريمو', 'name_en' => 'Primo', 'short_name' => 'بريمو', 'cat' => 'souqat', 'barcode' => '622000019000'],
                ['code' => 'ITM-00009', 'name_ar' => 'كليوباترا', 'name_en' => 'Cleopatra', 'short_name' => 'كليوباترا', 'cat' => 'souqat', 'barcode' => '622000019001'],
                ['code' => 'ITM-00010', 'name_ar' => 'locust', 'name_en' => 'Locust', 'short_name' => 'locust', 'cat' => 'souqat', 'barcode' => '622000019002'],
                ['code' => 'ITM-00011', 'name_ar' => 'castle', 'name_en' => 'Castle', 'short_name' => 'castle', 'cat' => 'souqat', 'barcode' => '622000019003'],
                ['code' => 'ITM-00012', 'name_ar' => 'ondon', 'name_en' => 'London', 'short_name' => 'london', 'cat' => 'souqat', 'barcode' => '622000019004'],
                ['code' => 'ITM-00013', 'name_ar' => 'marlboro', 'name_en' => 'Marlboro', 'short_name' => 'marlboro', 'cat' => 'souqat', 'barcode' => '622000019005'],
                ['code' => 'ITM-00014', 'name_ar' => 'lucky', 'name_en' => 'Lucky', 'short_name' => 'lucky', 'cat' => 'souqat', 'barcode' => '622000029000'],
                ['code' => 'ITM-00015', 'name_ar' => 'lm', 'name_en' => 'LM', 'short_name' => 'lm', 'cat' => 'souqat', 'barcode' => '622000029001'],
                ['code' => 'ITM-00016', 'name_ar' => 'pal mall', 'name_en' => 'Pall Mall', 'short_name' => 'pall mall', 'cat' => 'souqat', 'barcode' => '622000029002'],
                ['code' => 'ITM-00017', 'name_ar' => 'emarat', 'name_en' => 'Emarat', 'short_name' => 'emarat', 'cat' => 'souqat', 'barcode' => '622000029003'],
                ['code' => 'ITM-00018', 'name_ar' => 'rotmans', 'name_en' => 'Rothmans', 'short_name' => 'rothmans', 'cat' => 'souqat', 'barcode' => '622000029004'],
                ['code' => 'ITM-00019', 'name_ar' => 'kent', 'name_en' => 'Kent', 'short_name' => 'kent', 'cat' => 'souqat', 'barcode' => '622000029005'],
            ];

            foreach ($items as $itemData) {
                $category = $itemData['cat'] === 'souqat' ? $catSouqat : $catSigarettes;

                $item = Item::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $itemData['code']],
                    [
                        'product_company_id' => $mfg->id,
                        'item_category_id' => $category->id,
                        'name_ar' => $itemData['name_ar'],
                        'name_en' => $itemData['name_en'] ?? null,
                        'short_name' => $itemData['short_name'] ?? null,
                        'barcode' => $itemData['barcode'],
                        'base_unit_id' => $unitAlba->id,
                        'minimum_stock' => 10,
                        'maximum_stock' => 500,
                        'reorder_quantity' => 50,
                        'is_active' => true,
                        'is_taxable' => false,
                    ]
                );

                // Item Units
                ItemUnit::updateOrCreate(
                    ['item_id' => $item->id, 'unit_id' => $unitAlba->id],
                    [
                        'conversion_factor' => 1,
                        'is_purchase_unit' => true,
                        'is_sales_unit' => true,
                        'is_default' => true,
                        'purchase_price' => 47.85,
                        'sale_price' => 47.95,
                    ]
                );

                ItemUnit::updateOrCreate(
                    ['item_id' => $item->id, 'unit_id' => $unitKhatoota->id],
                    [
                        'conversion_factor' => 10,
                        'is_purchase_unit' => true,
                        'is_sales_unit' => true,
                        'is_default' => false,
                        'purchase_price' => 478.5,
                        'sale_price' => 479.5,
                    ]
                );

                ItemUnit::updateOrCreate(
                    ['item_id' => $item->id, 'unit_id' => $unitCarton->id],
                    [
                        'conversion_factor' => 500,
                        'is_purchase_unit' => true,
                        'is_sales_unit' => false,
                        'is_default' => false,
                        'purchase_price' => 23925,
                        'sale_price' => 23975,
                    ]
                );

                // Item Prices
                ItemPrice::updateOrCreate(
                    ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $unitAlba->id],
                    ['price' => 47.95, 'is_active' => true]
                );

                ItemPrice::updateOrCreate(
                    ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $unitKhatoota->id],
                    ['price' => 479.5, 'is_active' => true]
                );

                ItemPrice::updateOrCreate(
                    ['item_id' => $item->id, 'price_list_id' => $priceList->id, 'unit_id' => $unitCarton->id],
                    ['price' => 23975, 'is_active' => true]
                );

                // Item Barcodes
                if ($itemData['barcode']) {
                    ItemBarcode::updateOrCreate(
                        ['item_id' => $item->id, 'barcode' => $itemData['barcode']],
                        ['unit_id' => $unitAlba->id, 'is_default' => true]
                    );
                }
            }
        }

        $this->command->info('تم اضافة المنتجات والوحدات والأسعار بنجاح لجميع الشركات.');
        $this->command->info('- 6 وحدات');
        $this->command->info('- 19 منتج لكل شركة');
        $this->command->info('- 3 وحدات لكل منتج (علبة/خطوطة/كرتونة)');
        $this->command->info('- 3 أسعار لكل منتج');
    }

    private function seedUnits(): array
    {
        return [
            'UNT-001' => Unit::updateOrCreate(
                ['code' => 'UNT-001'],
                ['name_ar' => 'كرتونه', 'is_active' => true]
            ),
            'UNT-002' => Unit::updateOrCreate(
                ['code' => 'UNT-002'],
                ['name_ar' => 'خرطوشة', 'is_active' => true]
            ),
            'UNT-003' => Unit::updateOrCreate(
                ['code' => 'UNT-003'],
                ['name_ar' => 'علبه', 'is_active' => true]
            ),
            'ALBA' => Unit::updateOrCreate(
                ['code' => 'ALBA'],
                ['name_ar' => 'علبة', 'name_en' => 'Box', 'symbol' => 'box', 'is_active' => true]
            ),
            'KHAToota' => Unit::updateOrCreate(
                ['code' => 'KHAToota'],
                ['name_ar' => 'خطوطة', 'name_en' => 'Packet', 'symbol' => 'pkt', 'is_active' => true]
            ),
            'CARTON' => Unit::updateOrCreate(
                ['code' => 'CARTON'],
                ['name_ar' => 'كرتونة', 'name_en' => 'Carton', 'symbol' => 'ctn', 'is_active' => true]
            ),
        ];
    }
}
