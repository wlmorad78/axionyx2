<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\SubscriptionPlan;
use App\Models\CompanySubscription;

class CompanyAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. خطة اشتراك افتراضية
        $plan = SubscriptionPlan::firstOrCreate(
            ['code' => 'ENTERPRISE'],
            [
                'name' => 'خطة المؤسسات',
                'duration_months' => 12,
                'price' => 75000,
                'max_branches' => 999,
                'max_warehouses' => 999,
                'max_treasuries' => 999,
                'grace_period_days' => 15,
                'is_active' => true,
            ]
        );

        // 2. إنشاء الشركات
        $companies = [
            ['id' => 1, 'code' => 'AXIONYX', 'name_en' => 'Axionyx', 'name_ar' => 'أكسيونيكس', 'email' => 'admin@axionyx.com', 'phone' => '+966500000000'],
            ['id' => 2, 'code' => 'OBF-001', 'name_en' => 'Omar Fakry Mohamed Bassiouny & Partners', 'name_ar' => 'عمر فكرى محمد بسيونى و شركاه', 'email' => 'info@omarfakry.com', 'phone' => '01000000000'],
        ];

        foreach ($companies as $c) {
            DB::table('companies')->updateOrInsert(
                ['id' => $c['id']],
                array_merge($c, ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()])
            );

            CompanySubscription::updateOrCreate(
                ['company_id' => $c['id']],
                [
                    'subscription_plan_id' => $plan->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addYears(99)->toDateString(),
                    'amount' => 0,
                    'status' => 'active',
                ]
            );
        }

        $this->command->info("Companies created: 2");

        // 3. إنشاء المستخدمين
        $users = [
            [
                'usercode' => 2022,
                'name' => 'مدير النظام',
                'email' => 'admin@axionyx.com',
                'password' => Hash::make('123456'),
                'phone' => '01000000001',
                'is_active' => 1,
                'company_id' => 1,
            ],
            [
                'usercode' => 10001,
                'name' => 'مدير النظام',
                'email' => 'admin@omarfakry.com',
                'password' => Hash::make('123456'),
                'phone' => '01000000002',
                'is_active' => 1,
                'company_id' => 2,
            ],
        ];

        foreach ($users as $u) {

            DB::table('users')->updateOrInsert(
                ['usercode' => $u['usercode']],
                array_merge($u, ['created_at' => now(), 'updated_at' => now()])
            );

            DB::table('company_user')->updateOrInsert(
                ['user_id' => DB::table('users')->where('usercode', $u['usercode'])->value('id'), 'company_id' => $u['company_id']]
            );
        }

        $this->command->info("Users created: 2");
        $this->command->info("  2022 / 123456 (Super Admin) -> Axionyx");
        $this->command->info("  10001 / 123456 (Admin) -> عمر فكرى محمد بسيونى و شركاه");
    }
}
