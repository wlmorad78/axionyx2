<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Governorate;
use App\Models\City;
use App\Models\District;
use App\Models\Street;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedCurrencies();
        $this->seedPaymentMethods();
        $this->seedSubscriptionPlans();
    }

    private function seedCountries(): void
    {
        $countries = [
            ['code' => 'EG', 'iso2' => 'EG', 'name' => 'مصر', 'name_en' => 'Egypt', 'phone_code' => '+20', 'is_active' => true],
            ['code' => 'SA', 'iso2' => 'SA', 'name' => 'المملكة العربية السعودية', 'name_en' => 'Saudi Arabia', 'phone_code' => '+966', 'is_active' => true],
            ['code' => 'AE', 'iso2' => 'AE', 'name' => 'الإمارات العربية المتحدة', 'name_en' => 'United Arab Emirates', 'phone_code' => '+971', 'is_active' => true],
            ['code' => 'KW', 'iso2' => 'KW', 'name' => 'الكويت', 'name_en' => 'Kuwait', 'phone_code' => '+965', 'is_active' => true],
            ['code' => 'JO', 'iso2' => 'JO', 'name' => 'الأردن', 'name_en' => 'Jordan', 'phone_code' => '+962', 'is_active' => true],
        ];

        foreach ($countries as $data) {
            Country::updateOrCreate(['code' => $data['code']], $data);
        }

        // Governorates (Egypt)
        $egypt = Country::where('code', 'EG')->first();
        $governorates = [
            ['name' => 'الإسكندرية', 'code' => 'ALX'],
            ['name' => 'الجيزة', 'code' => 'GIZ'],
            ['name' => 'القاهرة', 'code' => 'CAI'],
            ['name' => 'القليوبية', 'code' => 'QLY'],
            ['name' => 'الشرقية', 'code' => 'SHQ'],
            ['name' => 'الدقهلية', 'code' => 'DKL'],
            ['name' => 'الغربية', 'code' => 'GHR'],
            ['name' => 'المنوفية', 'code' => 'MNF'],
            ['name' => 'البحيرة', 'code' => 'BHR'],
            ['name' => 'كفر الشيخ', 'code' => 'KFS'],
            ['name' => 'مطروح', 'code' => 'MAT'],
            ['name' => 'الإسماعيلية', 'code' => 'ISM'],
            ['name' => 'السويس', 'code' => 'SUZ'],
            ['name' => 'شمال سيناء', 'code' => 'SNS'],
            ['name' => 'جنوب سيناء', 'code' => 'STS'],
            ['name' => 'بني سويف', 'code' => 'BNS'],
            ['name' => 'الفيوم', 'code' => 'FYM'],
            ['name' => 'المنيا', 'code' => 'MIN'],
            ['name' => 'أسيوط', 'code' => 'AST'],
            ['name' => 'سوهاج', 'code' => 'SUH'],
            ['name' => 'قنا', 'code' => 'QNA'],
            ['name' => 'الأقصر', 'code' => 'LUX'],
            ['name' => 'أسوان', 'code' => 'ASW'],
            ['name' => 'البحر الأحمر', 'code' => 'BAH'],
            ['name' => 'الوادي الجديد', 'code' => 'WAD'],
            ['name' => 'محمية السلوم', 'code' => 'SLO'],
            ['name' => 'دمياط', 'code' => 'DMT'],
        ];

        foreach ($governorates as $g) {
            Governorate::updateOrCreate(
                ['name' => $g['name']],
                ['country_id' => $egypt->id, 'code' => $g['code'], 'is_active' => true]
            );
        }

        // Cities
        $citiesData = [
            ['gov' => 'الإسكندرية', 'cities' => ['الإسكندرية', 'العامرية', 'البرج', 'الدخيلة', 'سيدي جابر']],
            ['gov' => 'الجيزة', 'cities' => ['الجيزة', 'الهرم', 'الدقي', 'المهندسين', 'أكتوبر', 'السادس من أكتوبر', 'الفيوم']],
            ['gov' => 'القاهرة', 'cities' => ['القاهرة', 'مدينة نصر', 'المعادي', 'الزمالك', 'وسط البلد', 'مصر الجديدة', 'الشروق', 'العبور']],
        ];

        foreach ($citiesData as $gc) {
            $gov = Governorate::where('name', $gc['gov'])->first();
            if (!$gov) continue;
            foreach ($gc['cities'] as $cityName) {
                City::updateOrCreate(
                    ['governorate_id' => $gov->id, 'name' => $cityName],
                    ['is_active' => true]
                );
            }
        }

        // Districts
        $alexCity = City::where('name', 'الإسكندرية')->first();
        $gizaCity = City::where('name', 'الجيزة')->first();
        $cairoCity = City::where('name', 'القاهرة')->first();

        $districtsData = [
            ['city' => $alexCity, 'names' => ['حي الجمارك', 'حي المنتزه', 'حي العجمي', 'سيدي بشر']],
            ['city' => $gizaCity, 'names' => ['حي الدقي', 'حي الهرم', 'حي الشيخ زايد']],
            ['city' => $cairoCity, 'names' => ['حي المعادي', 'حي شبرا', 'الزمالك']],
        ];

        $createdDistricts = [];
        foreach ($districtsData as $dc) {
            if (!$dc['city']) continue;
            foreach ($dc['names'] as $dName) {
                $createdDistricts[] = District::updateOrCreate(
                    ['city_id' => $dc['city']->id, 'name' => $dName],
                    ['is_active' => true]
                );
            }
        }

        // Streets
        $streetsData = [
            [$createdDistricts[0]->id ?? null, 'شارع المينا الرئيسي'],
            [$createdDistricts[0]->id ?? null, 'شارع الكورنيش'],
            [$createdDistricts[3]->id ?? null, 'شارع فوزي معاذ'],
            [$createdDistricts[4]->id ?? null, 'شارع مصطفى كامل'],
            [$createdDistricts[6]->id ?? null, 'شارع 9 يوليو'],
        ];

        foreach ($streetsData as [$districtId, $name]) {
            if (!$districtId) continue;
            Street::updateOrCreate(
                ['district_id' => $districtId, 'name' => $name],
                ['is_active' => true]
            );
        }
    }

    private function seedCurrencies(): void
    {
        $currencies = [
            ['code' => 'EGP', 'symbol' => 'ج.م', 'name' => 'الجنيه المصري', 'name_en' => 'Egyptian Pound', 'exchange_rate' => 1, 'is_default' => true, 'is_active' => true],
            ['code' => 'USD', 'symbol' => '$', 'name' => 'الدولار الأمريكي', 'name_en' => 'US Dollar', 'exchange_rate' => 50.50, 'is_default' => false, 'is_active' => true],
            ['code' => 'SAR', 'symbol' => 'ر.س', 'name' => 'الريال السعودي', 'name_en' => 'Saudi Riyal', 'exchange_rate' => 13.47, 'is_default' => false, 'is_active' => true],
            ['code' => 'AED', 'symbol' => 'د.إ', 'name' => 'الدرهم الإماراتي', 'name_en' => 'UAE Dirham', 'exchange_rate' => 13.75, 'is_default' => false, 'is_active' => true],
            ['code' => 'EUR', 'symbol' => '€', 'name' => 'اليورو', 'name_en' => 'Euro', 'exchange_rate' => 54.80, 'is_default' => false, 'is_active' => true],
            ['code' => 'GBP', 'symbol' => '£', 'name' => 'الجنيه الإسترليني', 'name_en' => 'British Pound', 'exchange_rate' => 64.00, 'is_default' => false, 'is_active' => true],
            ['code' => 'KWD', 'symbol' => 'د.ك', 'name' => 'الدينار الكويتي', 'name_en' => 'Kuwaiti Dinar', 'exchange_rate' => 164.50, 'is_default' => false, 'is_active' => true],
        ];

        foreach ($currencies as $c) {
            Currency::updateOrCreate(['code' => $c['code']], $c);
        }
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            ['name' => 'نقدي', 'is_active' => true],
            ['name' => 'شيك', 'is_active' => true],
            ['name' => 'تحويل بنكي', 'is_active' => true],
            ['name' => 'بطاقة ائتمان', 'is_active' => true],
            ['name' => 'دفع إلكتروني', 'is_active' => true],
            ['name' => 'آجل', 'is_active' => true],
            ['name' => 'دفع جزئي', 'is_active' => true],
            ['name' => 'حوالة بريدية', 'is_active' => true],
        ];

        foreach ($methods as $m) {
            PaymentMethod::updateOrCreate(['name' => $m['name']], $m);
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            ['code' => 'BASIC', 'name' => 'الخطة الأساسية', 'duration_months' => 12, 'price' => 5000, 'max_branches' => 2, 'max_warehouses' => 3, 'max_treasuries' => 2, 'grace_period_days' => 5, 'is_active' => true],
            ['code' => 'STANDARD', 'name' => 'الخطة العادية', 'duration_months' => 12, 'price' => 15000, 'max_branches' => 5, 'max_warehouses' => 10, 'max_treasuries' => 5, 'grace_period_days' => 7, 'is_active' => true],
            ['code' => 'PREMIUM', 'name' => 'الخطة المتميزة', 'duration_months' => 12, 'price' => 35000, 'max_branches' => 15, 'max_warehouses' => 30, 'max_treasuries' => 15, 'grace_period_days' => 10, 'is_active' => true],
            ['code' => 'ENTERPRISE', 'name' => 'خطة المؤسسات', 'duration_months' => 12, 'price' => 75000, 'max_branches' => 999, 'max_warehouses' => 999, 'max_treasuries' => 999, 'grace_period_days' => 15, 'is_active' => true],
        ];

        foreach ($plans as $p) {
            SubscriptionPlan::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
