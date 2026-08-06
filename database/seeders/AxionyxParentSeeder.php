<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AxionyxParentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the parent company (Axionyx)
        $company = Company::firstOrCreate(
            ['code' => 'AXIONYX'],
            [
                'name_en' => 'Axionyx',
                'name_ar' => 'أكسيونيكس',
                'name' => 'Axionyx',
                'commercial_name_en' => 'Axionyx Software',
                'commercial_name_ar' => 'شركة أكسيونيكس للبرمجيات',
                'email' => 'admin@axionyx.com',
                'phone' => '+966500000000',
                'website' => 'https://axionyx.com',
                'address' => 'Riyadh, Saudi Arabia',
                'is_active' => true,
            ]
        );

        $this->command->info("Company: {$company->name_en} (ID: {$company->id})");

        // 2. Get the highest plan (corporate-elite)
        $plan = SubscriptionPlan::where('code', 'corporate-elite')->first();
        if (!$plan) {
            $this->command->error('Plan corporate-elite not found!');
            return;
        }

        // 3. Create subscription with no expiry
        CompanySubscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'subscription_plan_id' => $plan->id,
                'start_date' => now(),
                'end_date' => now()->addYears(99),
                'amount' => 0,
                'status' => 'active',
                'notes' => 'Parent company — unlimited access',
            ]
        );

        $this->command->info("Subscription: corporate-elite (unlimited)");

        // 4. Create Super Admin role
        $role = Role::firstOrCreate(
            ['code' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'company_id' => $company->id,
                'is_system' => true,
                'description' => 'Full system access — parent company admin',
            ]
        );

        // Assign ALL permissions to super_admin role
        $allPermissions = \App\Models\Permission::pluck('id')->toArray();
        $role->permissions()->sync($allPermissions);

        $this->command->info("Role: Super Admin — " . count($allPermissions) . " permissions");

        // 5. Create Super Admin user
        $user = User::firstOrCreate(
            ['usercode' => 1000],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@axionyx.com',
                'password' => Hash::make('axionyx2026'),
                'company_id' => $company->id,
                'is_active' => true,
            ]
        );

        $user->roles()->sync([$role->id]);

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  Axionyx Parent Company Created! 🚀');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info("  Company: {$company->name_en} (ID: {$company->id})");
        $this->command->info("  Plan:    {$plan->code} (unlimited)");
        $this->command->info("  Admin:   usercode=1000 / axionyx2026");
        $this->command->info("  Features: ALL (" . \App\Models\Feature::count() . " features)");
        $this->command->info("  Permissions: ALL (" . count($allPermissions) . ")");
        $this->command->info('═══════════════════════════════════════════');
    }
}
