<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Representative;
use App\Models\Role;
use App\Models\SalesTerritory;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->createUsers($company);
        }
    }

    private function createUsers(Company $company): void
    {
        $adminRole = Role::where('name', RoleNames::ADMIN)->where('company_id', $company->id)->first();
        $accountantRole = Role::where('name', RoleNames::ACCOUNTANT)->where('company_id', $company->id)->first();
        $warehouseRole = Role::where('name', RoleNames::WAREHOUSE_KEEPER)->where('company_id', $company->id)->first();
        $salesRepRole = Role::where('name', RoleNames::SALES_REP)->where('company_id', $company->id)->first();
        $salesManagerRole = Role::where('name', RoleNames::SALES_MANAGER)->where('company_id', $company->id)->first();

        $branch = Branch::where('company_id', $company->id)->first();

        $baseCode = ($company->id - 1) * 100;

        // Admin user
        $admin = User::updateOrCreate(
            ['usercode' => $baseCode + 1],
            [
                'name' => 'مدير شركة ' . $company->id,
                'password' => Hash::make('password'),
                'phone' => '010' . str_pad($baseCode + 1, 8, '0', STR_PAD_LEFT),
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );
        if ($adminRole) $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        if ($branch) {
            \DB::table('user_branches')->updateOrInsert(
                ['user_id' => $admin->id, 'branch_id' => $branch->id]
            );
        }

        // Accountant
        $accountant = User::updateOrCreate(
            ['usercode' => $baseCode + 2],
            [
                'name' => 'محاسب شركة ' . $company->id,
                'password' => Hash::make('password'),
                'phone' => '010' . str_pad($baseCode + 2, 8, '0', STR_PAD_LEFT),
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );
        if ($accountantRole) $accountant->roles()->syncWithoutDetaching([$accountantRole->id]);

        // Warehouse keeper
        $warehouseKeeper = User::updateOrCreate(
            ['usercode' => $baseCode + 3],
            [
                'name' => 'امين مخزن شركة ' . $company->id,
                'password' => Hash::make('password'),
                'phone' => '010' . str_pad($baseCode + 3, 8, '0', STR_PAD_LEFT),
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );
        if ($warehouseRole) $warehouseKeeper->roles()->syncWithoutDetaching([$warehouseRole->id]);

        // Sales manager
        $salesManager = User::updateOrCreate(
            ['usercode' => $baseCode + 4],
            [
                'name' => 'مدير مبيعات شركة ' . $company->id,
                'password' => Hash::make('password'),
                'phone' => '010' . str_pad($baseCode + 4, 8, '0', STR_PAD_LEFT),
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );
        if ($salesManagerRole) $salesManager->roles()->syncWithoutDetaching([$salesManagerRole->id]);

        // Sales representatives
        for ($i = 1; $i <= 10; $i++) {
            $rep = User::updateOrCreate(
                ['usercode' => $baseCode + 4 + $i],
                [
                    'name' => "مندوب $i - شركة $company->id",
                    'password' => Hash::make('password'),
                    'phone' => '010' . str_pad($baseCode + 4 + $i, 8, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'company_id' => $company->id,
                ]
            );
            if ($salesRepRole) $rep->roles()->syncWithoutDetaching([$salesRepRole->id]);
        }
    }
}
