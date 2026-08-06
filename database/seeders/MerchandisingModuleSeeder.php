<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\MerchandisingStandard;
use App\Models\DisplayLocation;
use Illuminate\Database\Seeder;

class MerchandisingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command?->warn('No company found. Skipping merchandising seeder.');
            return;
        }

        // --- Merchandising Standards ---
        $standards = [
            ['standard_code' => 'REFRIGERATOR_CLEAN', 'standard_name' => 'نظافة الثلاجة', 'description' => 'معايير نظافة وصيانة الثلاجات', 'max_score' => 100],
            ['standard_code' => 'SHELF_ORGANIZED', 'standard_name' => 'ترتيب الأرفف', 'description' => 'معايير ترتيب وتنظيم الأرفف', 'max_score' => 100],
            ['standard_code' => 'PRICE_TAG_AVAILABLE', 'standard_name' => 'ملصقات الأسعار', 'description' => 'وجود ودقة ملصقات الأسعار', 'max_score' => 50],
            ['standard_code' => 'DISPLAY_STANDARD', 'standard_name' => 'معيار العرض', 'description' => 'جودة وشكل العرض النهائي', 'max_score' => 100],
            ['standard_code' => 'PRODUCT_AVAILABILITY', 'standard_name' => 'توفر المنتجات', 'description' => 'نسبة توفر المنتجات المطلوبة', 'max_score' => 100],
        ];
        foreach ($standards as $s) {
            $std = MerchandisingStandard::updateOrCreate(
                ['standard_code' => $s['standard_code'], 'company_id' => $company->id],
                ['standard_name' => $s['standard_name'], 'description' => $s['description'], 'max_score' => $s['max_score'], 'is_active' => true]
            );

            // Add standard items for each standard
            if ($std->items()->count() === 0) {
                $items = match ($s['standard_code']) {
                    'REFRIGERATOR_CLEAN' => [
                        ['item_no' => 1, 'item_name' => 'نظافة الجدران الداخلية', 'score' => 20, 'display_order' => 1],
                        ['item_no' => 2, 'item_name' => 'نظافة الزجاج', 'score' => 15, 'display_order' => 2],
                        ['item_no' => 3, 'item_name' => 'temperature متوافقة', 'score' => 25, 'display_order' => 3],
                        ['item_no' => 4, 'item_name' => 'عدم وجود روائح', 'score' => 15, 'display_order' => 4],
                        ['item_no' => 5, 'item_name' => 'المصباح يعمل', 'score' => 10, 'display_order' => 5],
                        ['item_no' => 6, 'item_name' => 'الإطار سليم', 'score' => 15, 'display_order' => 6],
                    ],
                    'SHELF_ORGANIZED' => [
                        ['item_no' => 1, 'item_name' => 'ترتيب المنتجات حسب النوع', 'score' => 25, 'display_order' => 1],
                        ['item_no' => 2, 'item_name' => 'ملاءمة الارتفاع', 'score' => 20, 'display_order' => 2],
                        ['item_no' => 3, 'item_name' => 'عدم وجود فراغات', 'score' => 25, 'display_order' => 3],
                        ['item_no' => 4, 'item_name' => 'الوجوه الأمامية للأمام', 'score' => 15, 'display_order' => 4],
                        ['item_no' => 5, 'item_name' => ' تاريخ الصلاحية سليم', 'score' => 15, 'display_order' => 5],
                    ],
                    'PRICE_TAG_AVAILABLE' => [
                        ['item_no' => 1, 'item_name' => 'وجود ملصق سعر لكل منتج', 'score' => 25, 'display_order' => 1],
                        ['item_no' => 2, 'item_name' => 'دقة السعر', 'score' => 25, 'display_order' => 2],
                    ],
                    'DISPLAY_STANDARD' => [
                        ['item_no' => 1, 'item_name' => 'كفاية المساحة المخصصة', 'score' => 20, 'display_order' => 1],
                        ['item_no' => 2, 'item_name' => 'حالة المواد التسويقية', 'score' => 20, 'display_order' => 2],
                        ['item_no' => 3, 'item_name' => 'الظهور الجيد للعلامة التجارية', 'score' => 25, 'display_order' => 3],
                        ['item_no' => 4, 'item_name' => 'التوافق مع الخطة التسويقية', 'score' => 35, 'display_order' => 4],
                    ],
                    'PRODUCT_AVAILABILITY' => [
                        ['item_no' => 1, 'item_name' => 'توفر جميع المنتجات الأساسية', 'score' => 40, 'display_order' => 1],
                        ['item_no' => 2, 'item_name' => 'توفر منتجات العروض', 'score' => 30, 'display_order' => 2],
                        ['item_no' => 3, 'item_name' => 'عدم نفاد المخزون', 'score' => 30, 'display_order' => 3],
                    ],
                    default => [],
                };
                foreach ($items as $item) {
                    $std->items()->create($item);
                }
            }
        }

        // --- Display Locations ---
        $locations = [
            ['location_code' => 'MAIN_SHELF', 'location_name' => 'الرف الرئيسي', 'description' => 'الرف الرئيسي في المتجر'],
            ['location_code' => 'CHECKOUT', 'location_name' => 'بلاط الكاشير', 'description' => 'منطقة الدفع والكاشير'],
            ['location_code' => 'REFRIGERATOR', 'location_name' => 'الثلاجة', 'description' => 'الثلاجة العرضية'],
            ['location_code' => 'FREEZER', 'location_name' => 'الفريزر', 'description' => 'الفريزر'],
            ['location_code' => 'END_CAP', 'location_name' => 'نهاية الرف', 'description' => 'المساحة في نهاية الرف'],
            ['location_code' => 'DISPLAY_STAND', 'location_name' => 'ستاند العرض', 'description' => 'الستاند المستقل للعرض'],
            ['location_code' => 'PROMO_ZONE', 'location_name' => 'منطقة العروض', 'description' => 'المنطقة المخصصة للعروض الترويجية'],
            ['location_code' => 'ENTRANCE', 'location_name' => 'مدخل المتجر', 'description' => 'المنطقة القريبة من المدخل'],
        ];
        foreach ($locations as $l) {
            DisplayLocation::updateOrCreate(
                ['location_code' => $l['location_code'], 'company_id' => $company->id],
                ['location_name' => $l['location_name'], 'description' => $l['description']]
            );
        }
    }
}
