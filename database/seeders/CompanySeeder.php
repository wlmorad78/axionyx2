<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\CompanySubscriptionLimit;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'code' => 'CO-001',
                'name_ar' => 'شركة النيل للتجارة والتوزيع',
                'name_en' => 'Nile Trading & Distribution Co.',
                'tax_number' => '100-200-300',
                'commercial_register' => 'CR-12345',
                'phone' => '03-4567890',
                'mobile' => '01012345678',
                'email' => 'info@nile-trading.com',
                'address_line_1' => '42 شارع الجمهورية، الإسكندرية، مصر',
                'is_active' => true,
                'plan' => 'PREMIUM',
            ],
            [
                'code' => 'CO-002',
                'name_ar' => 'مؤسسة الرياض للخدمات اللوجستية',
                'name_en' => 'Riyadh Logistics Services',
                'tax_number' => '300-400-500',
                'commercial_register' => 'CR-67890',
                'phone' => '011-2345678',
                'mobile' => '0501234567',
                'email' => 'info@riyadh-logistics.com',
                'address_line_1' => '15 طريق الملك فهد، الرياض، السعودية',
                'is_active' => true,
                'plan' => 'STANDARD',
            ],
            [
                'code' => 'CO-003',
                'name_ar' => 'شركة الخليج للمنتجات الغذائية',
                'name_en' => 'Gulf Food Products Co.',
                'tax_number' => '500-600-700',
                'commercial_register' => 'CR-11223',
                'phone' => '04-3456789',
                'mobile' => '0509876543',
                'email' => 'info@gulf-foods.com',
                'address_line_1' => 'شارع الشيخ زايد، دبي، الإمارات',
                'is_active' => true,
                'plan' => 'ENTERPRISE',
            ],
        ];

        foreach ($companies as $data) {
            $planCode = $data['plan'];
            unset($data['plan']);

            $company = Company::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            // Create subscription
            $plan = SubscriptionPlan::where('code', $planCode)->first();
            $paymentMethod = PaymentMethod::where('name', 'تحويل بنكي')->first();

            if ($plan) {
                $subscription = CompanySubscription::updateOrCreate(
                    ['company_id' => $company->id, 'subscription_plan_id' => $plan->id],
                    [
                        'payment_method_id' => $paymentMethod?->id,
                        'start_date' => now()->toDateString(),
                        'end_date' => now()->addYear()->toDateString(),
                        'trial_end_date' => now()->addDays(14)->toDateString(),
                        'amount' => $plan->price,
                        'status' => 'active',
                    ]
                );

                CompanySubscriptionLimit::updateOrCreate(
                    ['company_subscription_id' => $subscription->id],
                    [
                        'max_branches' => $plan->max_branches,
                        'max_warehouses' => $plan->max_warehouses,
                        'max_treasuries' => $plan->max_treasuries,
                    ]
                );
            }

            // Create branches
            $branches = $this->getBranchesForCompany($company->id);
            foreach ($branches as $b) {
                Branch::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $b['code']],
                    [
                        'name' => $b['name'],
                        'name_ar' => $b['name'] ?? $b['name'],
                        'name_en' => $b['name_en'] ?? $b['name'],
                        'phone' => $b['phone'] ?? null,
                        'email' => $b['email'] ?? null,
                        'address' => $b['address'] ?? null,
                        'is_head_office' => $b['is_head_office'] ?? false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function getBranchesForCompany(int $companyId): array
    {
        return match ($companyId) {
            1 => [
                ['code' => 'BR-00001', 'name' => 'الفرع الرئيسي - الإسكندرية', 'name_en' => 'Head Office - Alex', 'phone' => '03-4567890', 'address' => '42 شارع الجمهورية، الإسكندرية', 'is_head_office' => true],
                ['code' => 'BR-00002', 'name' => 'فرع القاهرة', 'name_en' => 'Cairo Branch', 'phone' => '02-2345678', 'address' => 'شارع التحرير، القاهرة', 'is_head_office' => false],
                ['code' => 'BR-00003', 'name' => 'فرع الجيزة', 'name_en' => 'Giza Branch', 'phone' => '02-3456789', 'address' => 'شارع الهرم، الجيزة', 'is_head_office' => false],
            ],
            2 => [
                ['code' => 'BR-00001', 'name' => 'المقر الرئيسي - الرياض', 'name_en' => 'Head Office - Riyadh', 'phone' => '011-2345678', 'address' => '15 طريق الملك فهد، الرياض', 'is_head_office' => true],
                ['code' => 'BR-00002', 'name' => 'فرع جدة', 'name_en' => 'Jeddah Branch', 'phone' => '012-3456789', 'address' => 'شارع التحلية، جدة', 'is_head_office' => false],
            ],
            3 => [
                ['code' => 'BR-00001', 'name' => 'المقر الرئيسي - دبي', 'name_en' => 'Head Office - Dubai', 'phone' => '04-3456789', 'address' => 'شارع الشيخ زايد، دبي', 'is_head_office' => true],
                ['code' => 'BR-00002', 'name' => 'فرع أبوظبي', 'name_en' => 'Abu Dhabi Branch', 'phone' => '02-4567890', 'address' => 'شارع الشيخ زايد، أبوظبي', 'is_head_office' => false],
                ['code' => 'BR-00003', 'name' => 'فرع الشارقة', 'name_en' => 'Sharjah Branch', 'phone' => '06-5678901', 'address' => 'المنطقة الصناعية، الشارقة', 'is_head_office' => false],
            ],
            default => [
                ['code' => 'BR-00001', 'name' => 'الفرع الرئيسي', 'name_en' => 'Head Office', 'is_head_office' => true],
            ],
        };
    }
}
