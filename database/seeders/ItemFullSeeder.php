<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemSubCategory;
use App\Models\ItemUnit;
use App\Models\ItemPrice;
use App\Models\ItemBarcode;
use App\Models\PriceList;
use App\Models\ProductCompany;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ItemFullSeeder extends Seeder
{
    public function run(): void
    {
        $units = $this->seedUnits();
        $companies = Company::all();

        foreach ($companies as $company) {
            // Product Companies (Manufacturers)
            $manufacturers = [
                ['code' => 'MFG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'شركة النيل للصناعات الغذائية', 'name_en' => 'Nile Food Industries'],
                ['code' => 'MFG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'شركة الشرق للمنتجات', 'name_en' => 'Orient Products Co.'],
                ['code' => 'MFG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'مصنع الأهرام', 'name_en' => 'Pyramid Factory'],
            ];

            $mfgModels = [];
            foreach ($manufacturers as $m) {
                $mfgModels[] = ProductCompany::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $m['code']],
                    array_merge($m, ['is_active' => true])
                );
            }

            // Item Categories
            $categories = [
                ['code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'مشروبات', 'name_en' => 'Beverages'],
                ['code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'أغذية مجففة', 'name_en' => 'Dried Foods'],
                ['code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'منتجات ألبان', 'name_en' => 'Dairy Products'],
                ['code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-04', 'name_ar' => 'معلبات', 'name_en' => 'Canned Goods'],
                ['code' => 'CAT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-05', 'name_ar' => 'حلويات', 'name_en' => 'Confectionery'],
            ];

            $catModels = [];
            foreach ($categories as $c) {
                $catModels[] = ItemCategory::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $c['code']],
                    array_merge($c, ['is_active' => true])
                );
            }

            // Sub Categories
            $subCategories = [
                ['category' => 0, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'عصائر', 'name_en' => 'Juices'],
                ['category' => 0, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'מים معدنية', 'name_en' => 'Mineral Water'],
                ['category' => 1, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'أرز', 'name_en' => 'Rice'],
                ['category' => 1, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-04', 'name_ar' => 'معجون طماطم', 'name_en' => 'Tomato Paste'],
                ['category' => 2, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-05', 'name_ar' => 'لبان', 'name_en' => 'Milk'],
                ['category' => 3, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-06', 'name_ar' => 'تونة', 'name_en' => 'Tuna'],
                ['category' => 4, 'code' => 'SUB-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-07', 'name_ar' => 'شوكولاتة', 'name_en' => 'Chocolate'],
            ];

            foreach ($subCategories as $sc) {
                if (!isset($catModels[$sc['category']])) continue;
                ItemSubCategory::updateOrCreate(
                    ['item_category_id' => $catModels[$sc['category']]->id, 'code' => $sc['code']],
                    ['name_ar' => $sc['name_ar'], 'name_en' => $sc['name_en'], 'is_active' => true]
                );
            }

            // Items
            $items = [
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 1, 6, '0', STR_PAD_LEFT), 'name_ar' => 'عصير برتقال', 'name_en' => 'Orange Juice', 'cat' => 0, 'price' => 15, 'min' => 50, 'max' => 500, 'reorder' => 100],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 2, 6, '0', STR_PAD_LEFT), 'name_ar' => 'عصير مانجو', 'name_en' => 'Mango Juice', 'cat' => 0, 'price' => 18, 'min' => 50, 'max' => 500, 'reorder' => 100],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 3, 6, '0', STR_PAD_LEFT), 'name_ar' => 'مياه معدنية', 'name_en' => 'Mineral Water', 'cat' => 0, 'price' => 5, 'min' => 100, 'max' => 2000, 'reorder' => 500],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 4, 6, '0', STR_PAD_LEFT), 'name_ar' => 'أرز بسمتي', 'name_en' => 'Basmati Rice', 'cat' => 1, 'price' => 45, 'min' => 30, 'max' => 300, 'reorder' => 50],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 5, 6, '0', STR_PAD_LEFT), 'name_ar' => 'معجون طماطم', 'name_en' => 'Tomato Paste', 'cat' => 1, 'price' => 12, 'min' => 50, 'max' => 500, 'reorder' => 100],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 6, 6, '0', STR_PAD_LEFT), 'name_ar' => 'حليب طازج', 'name_en' => 'Fresh Milk', 'cat' => 2, 'price' => 22, 'min' => 40, 'max' => 400, 'reorder' => 80],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 7, 6, '0', STR_PAD_LEFT), 'name_ar' => 'جبنة بيضاء', 'name_en' => 'White Cheese', 'cat' => 2, 'price' => 35, 'min' => 30, 'max' => 200, 'reorder' => 50],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 8, 6, '0', STR_PAD_LEFT), 'name_ar' => 'تونة معلبة', 'name_en' => 'Canned Tuna', 'cat' => 3, 'price' => 28, 'min' => 40, 'max' => 400, 'reorder' => 80],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 9, 6, '0', STR_PAD_LEFT), 'name_ar' => 'فول مدمس', 'name_en' => 'Foul Medames', 'cat' => 3, 'price' => 10, 'min' => 60, 'max' => 600, 'reorder' => 120],
                ['code' => 'ITEM-' . str_pad($company->id * 100 + 10, 6, '0', STR_PAD_LEFT), 'name_ar' => 'شوكولاتة جالكسي', 'name_en' => 'Galaxy Chocolate', 'cat' => 4, 'price' => 25, 'min' => 50, 'max' => 500, 'reorder' => 100],
            ];

            $priceList = PriceList::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'PL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT)],
                ['name_ar' => 'القائمة الأساسية', 'name_en' => 'Main Price List', 'is_default' => true, 'is_active' => true]
            );

            $pcUnit = Unit::where('code', 'PCS')->first();
            $kgUnit = Unit::where('code', 'KG')->first();
            $caseUnit = Unit::where('code', 'CASE')->first();

            foreach ($items as $i => $itemData) {
                $item = Item::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $itemData['code']],
                    [
                        'product_company_id' => $mfgModels[$i % count($mfgModels)]?->id,
                        'item_category_id' => $catModels[$itemData['cat']]->id ?? $catModels[0]->id,
                        'name_ar' => $itemData['name_ar'],
                        'name_en' => $itemData['name_en'],
                        'barcode' => '622' . str_pad($company->id * 100 + $i + 1, 9, '0', STR_PAD_LEFT),
                        'base_unit_id' => $pcUnit?->id,
                        'minimum_stock' => $itemData['min'],
                        'maximum_stock' => $itemData['max'],
                        'reorder_quantity' => $itemData['reorder'],
                        'is_active' => true,
                    ]
                );

                // Item Units
                ItemUnit::updateOrCreate(
                    ['item_id' => $item->id, 'unit_id' => $pcUnit?->id],
                    ['conversion_factor' => 1, 'is_default' => true, 'is_purchase_unit' => true, 'is_sales_unit' => true]
                );

                if ($caseUnit) {
                    ItemUnit::updateOrCreate(
                        ['item_id' => $item->id, 'unit_id' => $caseUnit->id],
                        ['conversion_factor' => 12, 'is_default' => false, 'is_purchase_unit' => true, 'is_sales_unit' => false]
                    );
                }

                // Item Price
                ItemPrice::updateOrCreate(
                    ['item_id' => $item->id, 'price_list_id' => $priceList->id],
                    ['unit_id' => $pcUnit?->id, 'price' => $itemData['price'], 'is_active' => true]
                );

                // Item Barcode
                ItemBarcode::updateOrCreate(
                    ['item_id' => $item->id, 'barcode' => '622' . str_pad($company->id * 100 + $i + 1, 9, '0', STR_PAD_LEFT)],
                    ['unit_id' => $pcUnit?->id, 'is_default' => true]
                );
            }
        }
    }

    private function seedUnits(): array
    {
        $units = [
            ['code' => 'PCS', 'name_ar' => 'قطعة', 'name_en' => 'Piece', 'symbol' => 'pc'],
            ['code' => 'KG', 'name_ar' => 'كيلو', 'name_en' => 'Kilogram', 'symbol' => 'kg'],
            ['code' => 'G', 'name_ar' => 'جرام', 'name_en' => 'Gram', 'symbol' => 'g'],
            ['code' => 'L', 'name_ar' => 'لتر', 'name_en' => 'Liter', 'symbol' => 'L'],
            ['code' => 'ML', 'name_ar' => 'ميليلتر', 'name_en' => 'Milliliter', 'symbol' => 'ml'],
            ['code' => 'BOX', 'name_ar' => 'علبة', 'name_en' => 'Box', 'symbol' => 'box'],
            ['code' => 'CASE', 'name_ar' => 'كرتونة', 'name_en' => 'Case', 'symbol' => 'case'],
            ['code' => 'CTN', 'name_ar' => 'كرتنة', 'name_en' => 'Carton', 'symbol' => 'ctn'],
            ['code' => 'PKT', 'name_ar' => 'باكت', 'name_en' => 'Packet', 'symbol' => 'pkt'],
            ['code' => 'M', 'name_ar' => 'متر', 'name_en' => 'Meter', 'symbol' => 'm'],
        ];

        $result = [];
        foreach ($units as $u) {
            $result[] = Unit::updateOrCreate(['code' => $u['code']], $u);
        }
        return $result;
    }
}
