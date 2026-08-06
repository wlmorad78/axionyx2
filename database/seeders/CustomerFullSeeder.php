<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerClass;
use App\Models\CustomerGroup;
use App\Models\CustomerType;
use App\Models\CustomerAddress;
use App\Models\CustomerContact;
use App\Models\CustomerCreditLimit;
use App\Models\Country;
use App\Models\Governorate;
use App\Models\City;
use Illuminate\Database\Seeder;

class CustomerFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();
        $egypt = Country::where('code', 'EG')->first();
        $alexGov = Governorate::where('code', 'ALX')->first();
        $gizaGov = Governorate::where('code', 'GIZ')->first();
        $cairoGov = Governorate::where('code', 'CAI')->first();
        $alexCity = \App\Models\City::where('name', 'الإسكندرية')->first();
        $gizaCity = \App\Models\City::where('name', 'الجيزة')->first();
        $cairoCity = \App\Models\City::where('name', 'القاهرة')->first();

        foreach ($companies as $company) {
            // Customer Groups
            $groups = [
                ['code' => 'CG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'عملاء تجزئة', 'name_en' => 'Retail Customers'],
                ['code' => 'CG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'عملاء جملة', 'name_en' => 'Wholesale Customers'],
                ['code' => 'CG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'عملاء كبار', 'name_en' => 'Key Accounts'],
            ];

            $groupModels = [];
            foreach ($groups as $g) {
                $groupModels[] = CustomerGroup::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $g['code']],
                    array_merge($g, ['is_active' => true])
                );
            }

            // Customer Classes
            $classes = [
                ['code' => 'CC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'فئة ذهبية', 'name_en' => 'Gold', 'credit_limit' => 100000, 'discount_percentage' => 5, 'priority_level' => 1],
                ['code' => 'CC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'فئة فضية', 'name_en' => 'Silver', 'credit_limit' => 50000, 'discount_percentage' => 3, 'priority_level' => 2],
                ['code' => 'CC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'فئة عادية', 'name_en' => 'Regular', 'credit_limit' => 20000, 'discount_percentage' => 0, 'priority_level' => 3],
            ];

            $classModels = [];
            foreach ($classes as $c) {
                $classModels[] = CustomerClass::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $c['code']],
                    array_merge($c, ['is_active' => true])
                );
            }

            // Customer Types (skip if already exist)
            if (CustomerType::where('company_id', $company->id)->count() === 0) {
                $types = [
                    ['code' => 'CT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name_ar' => 'Key Account', 'name_en' => 'Key Account', 'is_protected' => true],
                    ['code' => 'CT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name_ar' => 'بقالة', 'name_en' => 'Grocery', 'is_protected' => false],
                    ['code' => 'CT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name_ar' => 'سوبر ماركت', 'name_en' => 'Supermarket', 'is_protected' => false],
                ];

                foreach ($types as $t) {
                    CustomerType::create(array_merge($t, [
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]));
                }
            }

            // Customers
            $customerNames = [
                ['ar' => 'محلات الأمل', 'en' => 'Al-Amal Stores', 'gov' => 'alex', 'phone' => '03-4561234'],
                ['ar' => 'سوبر ماركت النخبة', 'en' => 'Elite Supermarket', 'gov' => 'cairo', 'phone' => '02-2341234'],
                ['ar' => 'محلات السلامة', 'en' => 'Al-Salama Stores', 'gov' => 'giza', 'phone' => '02-3451234'],
                ['ar' => 'هايبر ماركت الرائد', 'en' => 'Pioneer Hypermarket', 'gov' => 'alex', 'phone' => '03-5671234'],
                ['ar' => 'محلات البدر', 'en' => 'Al-Badr Stores', 'gov' => 'cairo', 'phone' => '02-3456789'],
                ['ar' => 'سوبر ماركت الشروق', 'en' => 'Al-Shuruq Supermarket', 'gov' => 'giza', 'phone' => '02-4567890'],
                ['ar' => 'محلات الزهراء', 'en' => 'Al-Zahraa Stores', 'gov' => 'alex', 'phone' => '03-6781234'],
                ['ar' => 'متاجر الحياة', 'en' => 'Al-Hayat Stores', 'gov' => 'cairo', 'phone' => '02-5678901'],
                ['ar' => 'سوبر ماركت الفجر', 'en' => 'Al-Fajr Supermarket', 'gov' => 'giza', 'phone' => '02-6789012'],
                ['ar' => 'محلات التوفير', 'en' => 'Al-Tawfeer Stores', 'gov' => 'alex', 'phone' => '03-7891234'],
            ];

            foreach ($customerNames as $i => $c) {
                $govCode = $c['gov'];
                $gov = match($govCode) { 'alex' => $alexGov, 'cairo' => $cairoGov, 'giza' => $gizaGov, default => $alexGov };
                $city = match($govCode) { 'alex' => $alexCity, 'cairo' => $cairoCity, 'giza' => $gizaCity, default => $alexCity };

                $customer = Customer::updateOrCreate(
                    ['company_id' => $company->id, 'code' => 'CUST-' . str_pad($company->id * 100 + $i + 1, 6, '0', STR_PAD_LEFT)],
                    [
                        'customer_group_id' => $groupModels[$i % count($groupModels)]?->id,
                        'customer_class_id' => $classModels[$i % count($classModels)]?->id,
                        'name_ar' => $c['ar'],
                        'name_en' => $c['en'],
                        'phone' => $c['phone'],
                        'mobile' => '010' . str_pad(1000000 + $company->id * 100 + $i, 8, '0', STR_PAD_LEFT),
                        'email' => strtolower(str_replace(' ', '-', $c['en'])) . '@example.com',
                        'tax_number' => 'TAX-' . str_pad($company->id * 100 + $i + 1, 6, '0', STR_PAD_LEFT),
                        'credit_limit' => [100000, 50000, 20000][$i % 3],
                        'payment_term_days' => [30, 60, 15][$i % 3],
                        'is_active' => true,
                    ]
                );

                // Customer Address
                CustomerAddress::updateOrCreate(
                    ['customer_id' => $customer->id, 'is_default' => true],
                    [
                        'country_id' => $egypt?->id,
                        'governorate_id' => $gov?->id,
                        'city_id' => $city?->id,
                        'address_line_1' => 'شارع ' . ($i + 1) . ' - ' . ($c['gov'] === 'alex' ? 'الإسكندرية' : ($c['gov'] === 'cairo' ? 'القاهرة' : 'الجيزة')),
                        'latitude' => 31.2 + ($i * 0.01),
                        'longitude' => 29.9 + ($i * 0.01),
                        'is_default' => true,
                    ]
                );

                // Customer Contact
                CustomerContact::updateOrCreate(
                    ['customer_id' => $customer->id, 'is_primary' => true],
                    [
                        'name' => 'مدير المشتريات - ' . $c['ar'],
                        'position' => 'مدير المشتريات',
                        'mobile' => '010' . str_pad(2000000 + $i, 8, '0', STR_PAD_LEFT),
                        'email' => 'purchasing-' . ($i + 1) . '@example.com',
                        'is_primary' => true,
                    ]
                );

                // Customer Credit Limit
                CustomerCreditLimit::updateOrCreate(
                    ['customer_id' => $customer->id, 'effective_from' => '2026-01-01'],
                    [
                        'credit_limit' => $customer->credit_limit,
                        'effective_to' => '2026-12-31',
                    ]
                );
            }
        }
    }
}
