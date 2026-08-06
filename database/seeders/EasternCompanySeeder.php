<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\CompanySubscriptionLimit;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EasternCompanySeeder extends Seeder
{
    public function run(): void
    {
        // Create company
        $company = Company::updateOrCreate(
            ['code' => 'CO-004'],
            [
                'name_ar' => 'الشركة الشرقية ايسترن كومبانى',
                'name_en' => 'Eastern Company',
                'is_active' => true,
            ]
        );

        // Create subscription
        $plan = SubscriptionPlan::where('code', 'STANDARD')->first();
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

        // Create head office branch
        Branch::updateOrCreate(
            ['company_id' => $company->id, 'code' => 'BR-00001'],
            [
                'name' => 'الفرع الرئيسي',
                'name_ar' => 'الفرع الرئيسي',
                'name_en' => 'Head Office',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        // Create Admin role if not exists
        $adminRole = Role::firstOrCreate(
            ['name' => RoleNames::ADMIN, 'company_id' => $company->id],
            [
                'code' => 'ADMIN',
                'description' => 'مدير النظام',
            ]
        );

        // Create admin user with code 10001
        $admin = User::updateOrCreate(
            ['usercode' => 10001],
            [
                'name' => 'مدير الشركة الشرقية',
                'password' => Hash::make('password'),
                'phone' => '01000000000',
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );

        // Assign admin role
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        // Assign to head office branch
        $branch = Branch::where('company_id', $company->id)->first();
        if ($branch) {
            \DB::table('user_branches')->updateOrInsert(
                ['user_id' => $admin->id, 'branch_id' => $branch->id]
            );
        }

        $this->command->info("تم إنشاء الشركة: {$company->name_ar} ({$company->code})");
        $this->command->info("تم إنشاء المستخدم: {$admin->name} (كود: {$admin->usercode})");
    }
}
