<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\HR\Department;
use App\Models\HR\JobPosition;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CompanyStructureSeeder extends Seeder
{
    protected int $nextUsercode = 1000;

    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->cleanupExistingData();
            
            $company = $this->createCompany();
            $branches = $this->createBranches($company);
            $departments = $this->createDepartments($company);
            $roles = $this->createRoles($company);
            $this->createEmployees($company, $branches, $departments, $roles);
            $this->createGeneralManager($company, $branches, $roles);

            DB::commit();
            $this->command->info('Company structure created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function cleanupExistingData(): void
    {
        $this->command->info('Cleaning up existing data...');
        
        // Delete GM user by email (may have any company_id)
        $gmUser = User::where('email', 'gm@axionyx.com')->first();
        if ($gmUser) {
            DB::table('user_branches')->where('user_id', $gmUser->id)->delete();
            DB::table('user_roles')->where('user_id', $gmUser->id)->delete();
            DB::table('company_user')->where('user_id', $gmUser->id)->delete();
            Employee::where('user_id', $gmUser->id)->delete();
            $gmUser->delete();
        }

        // Delete all employees/users with @axionyx.com emails
        $emails = [
            'gm@axionyx.com',
            'hr.manager@axionyx.com',
        ];
        $branchPatterns = ['amiriya', 'al-hadra', 'al-sayf'];
        $roles = ['branch.manager', 'sales.manager', 'sales.rep', 'accountant', 'cashier', 'warehouse', 'distribution'];

        foreach ($branchPatterns as $branch) {
            foreach ($roles as $role) {
                $emails[] = "{$role}.{$branch}@axionyx.com";
            }
            $emails[] = "sales.rep1.{$branch}@axionyx.com";
            $emails[] = "sales.rep2.{$branch}@axionyx.com";
        }

        $userIds = User::whereIn('email', $emails)->pluck('id')->toArray();
        if (!empty($userIds)) {
            DB::table('user_branches')->whereIn('user_id', $userIds)->delete();
            DB::table('user_roles')->whereIn('user_id', $userIds)->delete();
            DB::table('company_user')->whereIn('user_id', $userIds)->delete();
            Employee::whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
        }

        // Cleanup company
        $company = Company::where('code', 'AXN001')->first();
        if ($company) {
            Employee::where('company_id', $company->id)->delete();
            $userIds = User::where('company_id', $company->id)->pluck('id');
            DB::table('user_branches')->whereIn('user_id', $userIds)->delete();
            DB::table('user_roles')->whereIn('user_id', $userIds)->delete();
            DB::table('company_user')->whereIn('user_id', $userIds)->delete();
            User::where('company_id', $company->id)->delete();
            Department::where('company_id', $company->id)->delete();
            Role::where('company_id', $company->id)->delete();
            Branch::where('company_id', $company->id)->delete();
            $company->delete();
        }
        
        $this->nextUsercode = User::max('usercode') + 1 ?? 1000;
    }

    private function createCompany(): Company
    {
        $this->command->info('Creating company...');

        return Company::firstOrCreate(
            ['code' => 'AXN001'],
            [
                'name_ar' => 'أكسيونكس للتجارة والتوزيع',
                'name_en' => 'Axionyx Trading & Distribution',
                'commercial_name_ar' => 'أكسيونكس',
                'commercial_name_en' => 'Axionyx',
                'tax_number' => '123456789',
                'phone' => '01000000000',
                'email' => 'info@axionyx.com',
                'is_active' => true,
            ]
        );
    }

    private function createBranches(Company $company): array
    {
        $this->command->info('Creating branches...');

        $branches = [];

        // Distribution branches
        $branches['amiriya_dist'] = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DIST-001'],
            [
                'name' => 'العامرية - توزيع',
                'name_ar' => 'العامرية - توزيع',
                'name_en' => 'Al-Amiriya - Distribution',
                'phone' => '01010000001',
                'is_head_office' => true,
                'is_active' => true,
            ]
        );

        $branches['hadra_dist'] = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DIST-002'],
            [
                'name' => 'الحضرة - توزيع',
                'name_ar' => 'الحضرة - توزيع',
                'name_en' => 'Al-Hadra - Distribution',
                'phone' => '01010000002',
                'is_head_office' => false,
                'is_active' => true,
            ]
        );

        // Wholesale branches
        $branches['sayf_wholesale'] = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'WH-001'],
            [
                'name' => 'السيوف - جملة',
                'name_ar' => 'السيوف - جملة',
                'name_en' => 'Al-Sayf - Wholesale',
                'phone' => '01010000003',
                'is_head_office' => false,
                'is_active' => true,
            ]
        );

        $branches['hadra_wholesale'] = Branch::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'WH-002'],
            [
                'name' => 'الحضرة - جملة',
                'name_ar' => 'الحضرة - جملة',
                'name_en' => 'Al-Hadra - Wholesale',
                'phone' => '01010000004',
                'is_head_office' => false,
                'is_active' => true,
            ]
        );

        return $branches;
    }

    private function createDepartments(Company $company): array
    {
        $this->command->info('Creating departments...');

        $departments = [];

        $departments['management'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-001'],
            [
                'name' => 'الإدارة العامة',
                'description' => 'الإدارة العليا للشركة',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $departments['sales'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-002'],
            [
                'name' => 'إدارة المبيعات',
                'description' => 'إدارة المبيعات والتوزيع',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $departments['accounting'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-003'],
            [
                'name' => 'الإدارة المالية والمحاسبية',
                'description' => 'المحاسبة والمالية',
                'sort_order' => 3,
                'is_active' => true,
            ]
        );

        $departments['warehouse'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-004'],
            [
                'name' => 'إدارة المخازن',
                'description' => 'إدارة المخازن والمخزون',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );

        $departments['hr'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-005'],
            [
                'name' => 'إدارة الموارد البشرية',
                'description' => 'الموظفين والتوظيف',
                'sort_order' => 5,
                'is_active' => true,
            ]
        );

        $departments['distribution'] = Department::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'DEPT-006'],
            [
                'name' => 'إدارة التوزيع',
                'description' => 'التوزيع والنقل',
                'sort_order' => 6,
                'is_active' => true,
            ]
        );

        return $departments;
    }

    private function createRoles(Company $company): array
    {
        $this->command->info('Creating roles...');

        $roles = [];

        // General Manager (Admin)
        $roles['general_manager'] = $this->createRole(
            $company->id,
            RoleNames::ADMIN,
            'admin_gm_' . $company->id,
            'مدير عام - 모든 الصلاحيات'
        );

        // Branch Manager
        $roles['branch_manager'] = $this->createRole(
            $company->id,
            RoleNames::BRANCH_MANAGER,
            'branch_manager_' . $company->id,
            'مدير الفرع'
        );

        // Sales Manager
        $roles['sales_manager'] = $this->createRole(
            $company->id,
            RoleNames::SALES_MANAGER,
            'sales_manager_' . $company->id,
            'مدير المبيعات'
        );

        // Sales Representative
        $roles['sales_rep'] = $this->createRole(
            $company->id,
            RoleNames::SALES_REP,
            'sales_rep_' . $company->id,
            'مندوب مبيعات'
        );

        // Accountant
        $roles['accountant'] = $this->createRole(
            $company->id,
            RoleNames::ACCOUNTANT,
            'accountant_' . $company->id,
            'محاسب'
        );

        // Warehouse Keeper
        $roles['warehouse_keeper'] = $this->createRole(
            $company->id,
            RoleNames::WAREHOUSE_KEEPER,
            'warehouse_keeper_' . $company->id,
            'أمين مخزن'
        );

        // Distribution Manager
        $roles['distribution_manager'] = $this->createRole(
            $company->id,
            RoleNames::DISTRIBUTION_MANAGER,
            'distribution_manager_' . $company->id,
            'مدير التوزيع'
        );

        // Cashier
        $roles['cashier'] = $this->createRole(
            $company->id,
            RoleNames::CASHIER,
            'cashier_' . $company->id,
            'أمين صندوق'
        );

        // HR Manager
        $roles['hr_manager'] = $this->createRole(
            $company->id,
            RoleNames::HR_MANAGER,
            'hr_manager_' . $company->id,
            'مدير الموارد البشرية'
        );

        return $roles;
    }

    private function createRole(int $companyId, string $name, string $code, string $description): Role
    {
        $role = Role::firstOrCreate(
            ['name' => $name, 'company_id' => $companyId],
            ['code' => $code, 'description' => $description]
        );

        // Assign permissions based on role
        $permissions = $this->getPermissionsForRole($name);
        if (!empty($permissions)) {
            $role->permissions()->sync($permissions);
        }

        return $role;
    }

    private function getPermissionsForRole(string $roleName): array
    {
        $defaults = config('permissions.defaults.' . $roleName, []);
        $permissionIds = [];

        foreach ($defaults as $pattern) {
            $matched = Permission::where(function ($query) use ($pattern) {
                if ($pattern === '*') {
                    $query->whereRaw('1=1');
                } elseif (str_ends_with($pattern, '.*')) {
                    $prefix = rtrim($pattern, '.*');
                    $query->where('code', 'like', $prefix . '.%');
                } else {
                    $query->where('code', $pattern);
                }
            })->pluck('id')->toArray();

            $permissionIds = array_merge($permissionIds, $matched);
        }

        return array_unique($permissionIds);
    }

    private function createEmployees(Company $company, array $branches, array $departments, array $roles): void
    {
        $this->command->info('Creating employees...');

        // Distribution branch - General Amiriya
        $this->createBranchEmployees(
            $company,
            $branches['amiriya_dist'],
            $departments,
            $roles,
            'العامرية'
        );

        // Distribution branch - Al-Hadra
        $this->createBranchEmployees(
            $company,
            $branches['hadra_dist'],
            $departments,
            $roles,
            'الحضرة'
        );

        // Wholesale branch - Al-Sayf
        $this->createBranchEmployees(
            $company,
            $branches['sayf_wholesale'],
            $departments,
            $roles,
            'السيوف'
        );

        // Wholesale branch - Al-Hadra
        $this->createBranchEmployees(
            $company,
            $branches['hadra_wholesale'],
            $departments,
            $roles,
            'الحضرة'
        );
    }

    private function createBranchEmployees(
        Company $company,
        Branch $branch,
        array $departments,
        array $roles,
        string $branchName
    ): void {
        $this->command->info("  Creating employees for {$branch->name}...");

        // Branch Manager
        $this->createEmployee(
            $company,
            $branch,
            $departments['management'],
            $roles['branch_manager'],
            [
                'first_name_ar' => 'مدير',
                'second_name_ar' => $branchName,
                'last_name_ar' => 'الفرع',
                'first_name_en' => 'Branch',
                'second_name_en' => $branchName,
                'last_name_en' => 'Manager',
                'email' => strtolower(str_replace(' ', '.', $branch->name)) . '@axionyx.com',
                'mobile' => $branch->phone,
            ]
        );

        // Sales Team
        $this->createEmployee(
            $company,
            $branch,
            $departments['sales'],
            $roles['sales_manager'],
            [
                'first_name_ar' => 'مدير',
                'second_name_ar' => 'مبيعات',
                'last_name_ar' => $branchName,
                'first_name_en' => 'Sales',
                'second_name_en' => 'Manager',
                'last_name_en' => $branchName,
                'email' => 'sales.manager.' . strtolower($branchName) . '@axionyx.com',
                'mobile' => '01020000001',
            ]
        );

        // Sales Reps (2 per branch)
        for ($i = 1; $i <= 2; $i++) {
            $this->createEmployee(
                $company,
                $branch,
                $departments['sales'],
                $roles['sales_rep'],
                [
                    'first_name_ar' => 'مندوب',
                    'second_name_ar' => $branchName,
                    'last_name_ar' => $i,
                    'first_name_en' => 'Sales Rep',
                    'second_name_en' => $branchName,
                    'last_name_en' => $i,
                    'email' => "sales.rep{$i}." . strtolower($branchName) . '@axionyx.com',
                    'mobile' => '010200000' . ($i + 10),
                ]
            );
        }

        // Accounting Team
        $this->createEmployee(
            $company,
            $branch,
            $departments['accounting'],
            $roles['accountant'],
            [
                'first_name_ar' => 'محاسب',
                'second_name_ar' => $branchName,
                'last_name_ar' => 'الفرع',
                'first_name_en' => 'Accountant',
                'second_name_en' => $branchName,
                'last_name_en' => '',
                'email' => 'accountant.' . strtolower($branchName) . '@axionyx.com',
                'mobile' => '01030000001',
            ]
        );

        // Cashier
        $this->createEmployee(
            $company,
            $branch,
            $departments['accounting'],
            $roles['cashier'],
            [
                'first_name_ar' => 'أمين',
                'second_name_ar' => 'صندوق',
                'last_name_ar' => $branchName,
                'first_name_en' => 'Cashier',
                'second_name_en' => $branchName,
                'last_name_en' => '',
                'email' => 'cashier.' . strtolower($branchName) . '@axionyx.com',
                'mobile' => '01030000002',
            ]
        );

        // Warehouse Team
        $this->createEmployee(
            $company,
            $branch,
            $departments['warehouse'],
            $roles['warehouse_keeper'],
            [
                'first_name_ar' => 'أمين',
                'second_name_ar' => 'مخزن',
                'last_name_ar' => $branchName,
                'first_name_en' => 'Warehouse',
                'second_name_en' => 'Keeper',
                'last_name_en' => $branchName,
                'email' => 'warehouse.' . strtolower($branchName) . '@axionyx.com',
                'mobile' => '01040000001',
            ]
        );

        // Distribution Team (for distribution branches only)
        if (str_contains($branch->name, 'توزيع')) {
            $this->createEmployee(
                $company,
                $branch,
                $departments['distribution'],
                $roles['distribution_manager'],
                [
                    'first_name_ar' => 'مدير',
                    'second_name_ar' => 'توزيع',
                    'last_name_ar' => $branchName,
                    'first_name_en' => 'Distribution',
                    'second_name_en' => 'Manager',
                    'last_name_en' => $branchName,
                    'email' => 'distribution.' . strtolower($branchName) . '@axionyx.com',
                    'mobile' => '01050000001',
                ]
            );
        }

        // HR Manager (only for head office)
        if ($branch->is_head_office) {
            $this->createEmployee(
                $company,
                $branch,
                $departments['hr'],
                $roles['hr_manager'],
                [
                    'first_name_ar' => 'مدير',
                    'second_name_ar' => 'الموارد البشرية',
                    'last_name_ar' => '',
                    'first_name_en' => 'HR',
                    'second_name_en' => 'Manager',
                    'last_name_en' => '',
                    'email' => 'hr.manager@axionyx.com',
                    'mobile' => '01060000001',
                ]
            );
        }
    }

    private function createEmployee(
        Company $company,
        Branch $branch,
        Department $department,
        Role $role,
        array $data
    ): Employee {
        $usercode = $this->getNextUsercode();

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'usercode' => $usercode,
                'name' => trim($data['first_name_en'] . ' ' . $data['second_name_en'] . ' ' . $data['last_name_en']),
                'password' => Hash::make('1234'),
                'phone' => $data['mobile'],
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );

        $user->companies()->syncWithoutDetaching([$company->id]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        DB::table('user_branches')->insertOrIgnore([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'is_default' => true,
        ]);

        $employee = Employee::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $user->id],
            [
                'employee_code' => 'EMP-' . str_pad($usercode, 4, '0', STR_PAD_LEFT),
                'first_name_ar' => $data['first_name_ar'],
                'second_name_ar' => $data['second_name_ar'],
                'last_name_ar' => $data['last_name_ar'],
                'first_name_en' => $data['first_name_en'],
                'second_name_en' => $data['second_name_en'],
                'last_name_en' => $data['last_name_en'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
                'department_id' => $department->id,
                'hire_date' => now(),
                'is_active' => true,
            ]
        );

        return $employee;
    }

    private function getNextUsercode(): int
    {
        return $this->nextUsercode++;
    }

    private function createGeneralManager(Company $company, array $branches, array $roles): void
    {
        $this->command->info('Creating general manager...');

        $gmUser = User::firstOrCreate(
            ['email' => 'gm@axionyx.com'],
            [
                'usercode' => 1000,
                'name' => 'المدير العام',
                'password' => Hash::make('1234'),
                'phone' => '01000000001',
                'is_active' => true,
                'company_id' => $company->id,
            ]
        );

        $gmUser->companies()->syncWithoutDetaching([$company->id]);
        $gmUser->roles()->syncWithoutDetaching([$roles['general_manager']->id]);

        // Assign to all branches
        foreach ($branches as $branch) {
            DB::table('user_branches')->insertOrIgnore([
                'user_id' => $gmUser->id,
                'branch_id' => $branch->id,
                'is_default' => $branch->is_head_office,
            ]);
        }

        $department = Department::where('company_id', $company->id)->where('code', 'DEPT-001')->first();

        Employee::firstOrCreate(
            ['company_id' => $company->id, 'user_id' => $gmUser->id],
            [
                'employee_code' => 'EMP-1000',
                'first_name_ar' => 'المدير',
                'second_name_ar' => 'العام',
                'last_name_ar' => '',
                'first_name_en' => 'General',
                'second_name_en' => 'Manager',
                'last_name_en' => '',
                'email' => 'gm@axionyx.com',
                'mobile' => '01000000001',
                'department_id' => $department?->id,
                'hire_date' => now(),
                'is_active' => true,
            ]
        );

        // Update next usercode to start after GM
        $this->nextUsercode = 1001;
    }
}
