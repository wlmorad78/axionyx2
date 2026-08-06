<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\CompanySubscriptionLimit;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Role;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;

class PlanDemoUserSeeder extends Seeder
{
    private array $demoCompanies = [
        1 => [
            'code' => 'PLAN-001', 'name_ar' => 'شركة الموزع المستقل', 'name_en' => 'Starter Distribution Co.',
            'phone' => '01010000001', 'email' => 'starter@axionyx-demo.com', 'address_line_1' => 'القاهرة، مصر',
            'user_name' => 'مدير Starter', 'user_phone' => '01010000010', 'user_email' => 'admin-starter@axionyx-demo.com',
        ],
        2 => [
            'code' => 'PLAN-002', 'name_ar' => 'مؤسسة تاجر الجملة', 'name_en' => 'Growth Wholesale Co.',
            'phone' => '01020000002', 'email' => 'growth@axionyx-demo.com', 'address_line_1' => 'الإسكندرية، مصر',
            'user_name' => 'مدير Growth', 'user_phone' => '01020000020', 'user_email' => 'admin-growth@axionyx-demo.com',
        ],
        3 => [
            'code' => 'PLAN-003', 'name_ar' => 'شركة التوكيل للتوزيع', 'name_en' => 'Professional Agency',
            'phone' => '01030000003', 'email' => 'professional@axionyx-demo.com', 'address_line_1' => 'الجيزة، مصر',
            'user_name' => 'مدير Professional', 'user_phone' => '01030000030', 'user_email' => 'admin-professional@axionyx-demo.com',
        ],
        4 => [
            'code' => 'PLAN-004', 'name_ar' => 'شركةEnterprise للتجارة', 'name_en' => 'Enterprise Trading Co.',
            'phone' => '01040000004', 'email' => 'enterprise@axionyx-demo.com', 'address_line_1' => 'المنصورة، مصر',
            'user_name' => 'مدير Enterprise', 'user_phone' => '01040000040', 'user_email' => 'admin-enterprise@axionyx-demo.com',
        ],
        5 => [
            'code' => 'PLAN-005', 'name_ar' => 'شركةCorporate للخدمات', 'name_en' => 'Corporate Services Co.',
            'phone' => '01050000005', 'email' => 'corporate@axionyx-demo.com', 'address_line_1' => 'طنطا، مصر',
            'user_name' => 'مدير Corporate', 'user_phone' => '01050000050', 'user_email' => 'admin-corporate@axionyx-demo.com',
        ],
        6 => [
            'code' => 'PLAN-006', 'name_ar' => 'شركةCorporate Elite الضخمة', 'name_en' => 'Corporate Elite Corp.',
            'phone' => '01060000006', 'email' => 'elite@axionyx-demo.com', 'address_line_1' => 'أسوان، مصر',
            'user_name' => 'مدير Corporate Elite', 'user_phone' => '01060000060', 'user_email' => 'admin-elite@axionyx-demo.com',
        ],
    ];

    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => RoleNames::ADMIN], ['code' => 'admin']);

        foreach ($this->demoCompanies as $tier => $data) {
            $plan = SubscriptionPlan::where('tier', $tier)->first();
            if (!$plan) {
                continue;
            }

            $company = Company::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name_ar' => $data['name_ar'],
                    'name_en' => $data['name_en'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address_line_1' => $data['address_line_1'],
                    'is_active' => true,
                ]
            );

            CompanySubscription::updateOrCreate(
                ['company_id' => $company->id, 'subscription_plan_id' => $plan->id],
                [
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addYear()->toDateString(),
                    'trial_end_date' => now()->addDays($plan->grace_period_days)->toDateString(),
                    'amount' => $plan->price,
                    'status' => 'active',
                ]
            );

            $usercode = User::FIRST_USERCODE + $tier + 100;
            $user = User::updateOrCreate(
                ['usercode' => $usercode],
                [
                    'name' => $data['user_name'],
                    'phone' => $data['user_phone'],
                    'email' => $data['user_email'],
                    'password' => 'password',
                    'is_active' => true,
                    'company_id' => $company->id,
                ]
            );

            $user->roles()->syncWithoutDetaching([$adminRole->id]);
            $user->companies()->syncWithoutDetaching([$company->id]);
        }
    }
}
