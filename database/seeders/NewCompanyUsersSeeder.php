<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NewCompanyUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Company: Axionyx
        $company1 = Company::updateOrCreate(
            ['code' => 'CO-004'],
            [
                'name' => 'Axionyx',
                'name_ar' => 'Axionyx',
                'name_en' => 'Axionyx',
                'is_active' => true,
            ]
        );

        // Branch for Axionyx
        $branch1 = Branch::updateOrCreate(
            ['company_id' => $company1->id, 'code' => 'BR-00001'],
            [
                'name' => 'الفرع الرئيسي',
                'name_en' => 'Head Office',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        // Global Admin role (no company)
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Global Admin', 'company_id' => null],
            ['code' => 'global_admin']
        );

        // User: Super Admin - code 10000
        $user1 = User::updateOrCreate(
            ['usercode' => 10000],
            [
                'name' => 'مدير النظام - Axionyx',
                'password' => Hash::make('123456'),
                'phone' => '01000000000',
                'is_active' => true,
                'company_id' => $company1->id,
            ]
        );
        $user1->roles()->syncWithoutDetaching([$superAdminRole->id]);
        \DB::table('user_branches')->updateOrInsert(
            ['user_id' => $user1->id, 'branch_id' => $branch1->id]
        );

        // 2. Company: موزع الشكة الشرقيه
        $company2 = Company::updateOrCreate(
            ['code' => 'CO-005'],
            [
                'name' => 'موزع الشكة الشرقيه',
                'name_ar' => 'موزع الشكة الشرقيه',
                'name_en' => 'Eastern Souqat Distributor',
                'is_active' => true,
            ]
        );

        // Branch for موزع الشكة الشرقيه
        $branch2 = Branch::updateOrCreate(
            ['company_id' => $company2->id, 'code' => 'BR-00001'],
            [
                'name' => 'الفرع الرئيسي',
                'name_en' => 'Head Office',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        // Role for موزع الشكة الشرقيه
        $adminRole2 = Role::firstOrCreate(
            ['name' => RoleNames::ADMIN, 'company_id' => $company2->id],
            ['code' => 'admin_' . $company2->id]
        );

        // User: Admin - code 10001
        $user2 = User::updateOrCreate(
            ['usercode' => 10001],
            [
                'name' => 'مدير موزع الشكة الشرقيه',
                'password' => Hash::make('123456'),
                'phone' => '01000000001',
                'is_active' => true,
                'company_id' => $company2->id,
            ]
        );
        $user2->roles()->syncWithoutDetaching([$adminRole2->id]);
        \DB::table('user_branches')->updateOrInsert(
            ['user_id' => $user2->id, 'branch_id' => $branch2->id]
        );

        $this->command->info('تم إضافة الشركات والمستخدمين بنجاح:');
        $this->command->info('- Axionyx | Super Admin | code: 10000 | password: 123456');
        $this->command->info('- موزع الشكة الشرقيه | Admin | code: 10001 | password: 123456');
    }
}
