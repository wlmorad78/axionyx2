<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\MarketingAssetCategory;
use App\Models\MerchandisingChecklist;
use App\Models\SurveyCategory;
use App\Models\PriceLevel;
use Illuminate\Database\Seeder;

class CompanyScopedReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command?->warn('No company found. Skipping company-scoped seeder.');
            return;
        }

        // --- Marketing Asset Categories ---
        $assetCategories = [
            ['code' => 'FRIDGE', 'name' => 'ثلاجة'],
            ['code' => 'FREEZER', 'name' => 'فريزر'],
            ['code' => 'DISPLAY_STAND', 'name' => 'ستاند عرض'],
            ['code' => 'SHELF', 'name' => 'رف'],
            ['code' => 'BANNER', 'name' => 'لافتة'],
            ['code' => 'SCREEN', 'name' => 'شاشة'],
            ['code' => 'UMBRLLA', 'name' => 'شمسية'],
            ['code' => 'STANDEE', 'name' => 'ستاندي'],
            ['code' => 'POSTER', 'name' => 'بوستر'],
            ['code' => 'COOLER', 'name' => 'مبرد'],
        ];
        foreach ($assetCategories as $c) {
            MarketingAssetCategory::updateOrCreate(
                ['code' => $c['code'], 'company_id' => $company->id],
                ['name' => $c['name'], 'is_active' => true]
            );
        }

        // --- Merchandising Checklists ---
        $checklists = [
            ['check_code' => 'MC001', 'check_name' => 'نظافة الثلاجة', 'max_score' => 10],
            ['check_code' => 'MC002', 'check_name' => 'ترتيب الأرفف', 'max_score' => 10],
            ['check_code' => 'MC003', 'check_name' => 'توفر المنتجات', 'max_score' => 10],
            ['check_code' => 'MC004', 'check_name' => 'وجود ملصقات الأسعار', 'max_score' => 5],
            ['check_code' => 'MC005', 'check_name' => 'توفر المواد التسويقية', 'max_score' => 10],
            ['check_code' => 'MC006', 'check_name' => 'حالة العرض', 'max_score' => 10],
            ['check_code' => 'MC007', 'check_name' => 'التواريخ الصالحة', 'max_score' => 5],
            ['check_code' => 'MC008', 'check_name' => 'المساحة المخصصة', 'max_score' => 10],
        ];
        foreach ($checklists as $c) {
            MerchandisingChecklist::updateOrCreate(
                ['check_code' => $c['check_code'], 'company_id' => $company->id],
                ['check_name' => $c['check_name'], 'max_score' => $c['max_score'], 'is_active' => true]
            );
        }

        // --- Survey Categories ---
        $surveyCategories = [
            ['code' => 'CSAT', 'name' => 'رضا العملاء', 'description' => 'استبيانات قياس رضا العملاء'],
            ['code' => 'MKT_RES', 'name' => 'بحث السوق', 'description' => 'استبيانات بحث السوق'],
            ['code' => 'COMP_ANA', 'name' => 'تحليل المنافسين', 'description' => 'استبيانات تحليل المنافسين'],
            ['code' => 'MERCH', 'name' => 'التسوّق', 'description' => 'استبيانات التسوّق والمراقبة'],
            ['code' => 'PROD_FB', 'name' => 'ملاحظات المنتج', 'description' => 'استبيانات ملاحظات المنتجات'],
        ];
        foreach ($surveyCategories as $c) {
            SurveyCategory::updateOrCreate(
                ['code' => $c['code'], 'company_id' => $company->id],
                ['name' => $c['name'], 'description' => $c['description'], 'is_active' => true]
            );
        }

        // --- Price Levels ---
        $priceLevels = [
            ['level_code' => 'RETAIL', 'level_name' => 'سعر التجزئة', 'priority' => 1],
            ['level_code' => 'WHOLESALE', 'level_name' => 'سعر الجملة', 'priority' => 2],
            ['level_code' => 'VIP', 'level_name' => 'سعر VIP', 'priority' => 3],
            ['level_code' => 'DISTRIBUTOR', 'level_name' => 'سعر الموزع', 'priority' => 4],
            ['level_code' => 'PROMO', 'level_name' => 'سعر العرض', 'priority' => 5],
        ];
        foreach ($priceLevels as $p) {
            PriceLevel::updateOrCreate(
                ['level_code' => $p['level_code'], 'company_id' => $company->id],
                ['level_name' => $p['level_name'], 'priority' => $p['priority'], 'is_active' => true]
            );
        }
    }
}
