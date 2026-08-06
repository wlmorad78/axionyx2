<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeContract;
use App\Models\EmployeeStatus;
use App\Models\ContractType;
use App\Models\ContractStatus;
use App\Models\JobGrade;
use App\Models\JobTitle;
use App\Models\JobFamily;
use App\Models\Role;
use App\Models\SalaryScale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates employees for ALL existing users with proper department/position assignments.
 * Works with any users - not dependent on UserSeeder.
 */
class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHrReferenceData();
        $this->seedDepartments();
        $this->createEmployeesForAllUsers();
    }

    private function seedHrReferenceData(): void
    {
        $families = [
            ['code' => 'SALES', 'name_ar' => 'المبيعات', 'name_en' => 'Sales'],
            ['code' => 'FINANCE', 'name_ar' => 'المالية', 'name_en' => 'Finance'],
            ['code' => 'HR', 'name_ar' => 'الموارد البشرية', 'name_en' => 'HR'],
            ['code' => 'WAREHOUSE', 'name_ar' => 'المخازن', 'name_en' => 'Warehouse'],
            ['code' => 'ADMIN', 'name_ar' => 'الإدارة', 'name_en' => 'Administration'],
        ];

        $companyId = Company::first()?->id ?? 1;
        $famModels = [];
        foreach ($families as $f) {
            $famModels[$f['code']] = JobFamily::updateOrCreate(
                ['code' => $f['code']],
                array_merge($f, ['company_id' => $companyId])
            );
        }

        $titles = [
            ['code' => 'SALES_REP', 'family' => 'SALES', 'name_ar' => 'مندوب مبيعات', 'name_en' => 'Sales Representative'],
            ['code' => 'SALES_SUP', 'family' => 'SALES', 'name_ar' => 'مشرف مبيعات', 'name_en' => 'Sales Supervisor'],
            ['code' => 'SALES_MGR', 'family' => 'SALES', 'name_ar' => 'مدير مبيعات', 'name_en' => 'Sales Manager'],
            ['code' => 'ACC', 'family' => 'FINANCE', 'name_ar' => 'محاسب', 'name_en' => 'Accountant'],
            ['code' => 'CHIEF_ACC', 'family' => 'FINANCE', 'name_ar' => 'رئيس المحاسبين', 'name_en' => 'Chief Accountant'],
            ['code' => 'WH_KEEPER', 'family' => 'WAREHOUSE', 'name_ar' => 'أمين مخزن', 'name_en' => 'Warehouse Keeper'],
            ['code' => 'WH_MGR', 'family' => 'WAREHOUSE', 'name_ar' => 'مدير مخزن', 'name_en' => 'Warehouse Manager'],
            ['code' => 'HR_MGR', 'family' => 'HR', 'name_ar' => 'مدير الموارد البشرية', 'name_en' => 'HR Manager'],
            ['code' => 'GEN_MGR', 'family' => 'ADMIN', 'name_ar' => 'مدير عام', 'name_en' => 'General Manager'],
            ['code' => 'DIST_MGR', 'family' => 'ADMIN', 'name_ar' => 'مدير توزيع', 'name_en' => 'Distribution Manager'],
            ['code' => 'CASHIER', 'family' => 'FINANCE', 'name_ar' => 'أمين صندوق', 'name_en' => 'Cashier'],
        ];

        foreach ($titles as $t) {
            JobTitle::updateOrCreate(
                ['code' => $t['code']],
                ['company_id' => $companyId, 'job_family_id' => $famModels[$t['family']]->id, 'name_ar' => $t['name_ar'], 'name_en' => $t['name_en']]
            );
        }

        $grades = [
            ['code' => 'G1', 'name_ar' => 'الدرجة الأولى', 'name_en' => 'Grade 1', 'grade_level' => 1],
            ['code' => 'G2', 'name_ar' => 'الدرجة الثانية', 'name_en' => 'Grade 2', 'grade_level' => 2],
            ['code' => 'G3', 'name_ar' => 'الدرجة الثالثة', 'name_en' => 'Grade 3', 'grade_level' => 3],
            ['code' => 'G4', 'name_ar' => 'الدرجة الرابعة', 'name_en' => 'Grade 4', 'grade_level' => 4],
            ['code' => 'G5', 'name_ar' => 'الدرجة الخامسة', 'name_en' => 'Grade 5', 'grade_level' => 5],
        ];

        $gradeModels = [];
        foreach ($grades as $g) {
            $gradeModels[$g['code']] = JobGrade::updateOrCreate(
                ['code' => $g['code']],
                array_merge($g, ['company_id' => $companyId])
            );
        }

        $scales = [
            ['code' => 'SS-G1', 'grade' => 'G1', 'name_ar' => 'سلم الدرجة 1', 'name_en' => 'Scale G1', 'min' => 5000, 'max' => 7000],
            ['code' => 'SS-G2', 'grade' => 'G2', 'name_ar' => 'سلم الدرجة 2', 'name_en' => 'Scale G2', 'min' => 7001, 'max' => 10000],
            ['code' => 'SS-G3', 'grade' => 'G3', 'name_ar' => 'سلم الدرجة 3', 'name_en' => 'Scale G3', 'min' => 10001, 'max' => 15000],
            ['code' => 'SS-G4', 'grade' => 'G4', 'name_ar' => 'سلم الدرجة 4', 'name_en' => 'Scale G4', 'min' => 15001, 'max' => 22000],
            ['code' => 'SS-G5', 'grade' => 'G5', 'name_ar' => 'سلم الدرجة 5', 'name_en' => 'Scale G5', 'min' => 22001, 'max' => 35000],
        ];

        foreach ($scales as $s) {
            SalaryScale::updateOrCreate(
                ['code' => $s['code']],
                ['company_id' => $companyId, 'job_grade_id' => $gradeModels[$s['grade']]->id, 'name_ar' => $s['name_ar'], 'name_en' => $s['name_en'], 'minimum_salary' => $s['min'], 'maximum_salary' => $s['max']]
            );
        }

        EmployeeStatus::updateOrCreate(['code' => 'ACTIVE'], ['name_ar' => 'نشط', 'name_en' => 'Active', 'color' => '#22C55E', 'is_system' => true]);
        ContractType::firstOrCreate(['code' => 'PERMANENT'], ['name_ar' => 'دائم', 'name_en' => 'Permanent', 'is_active' => true]);
        ContractStatus::firstOrCreate(['code' => 'ACTIVE'], ['name_ar' => 'نشط', 'name_en' => 'Active', 'is_active' => true]);
    }

    private function seedDepartments(): void
    {
        $departments = [
            ['code' => 'GEN', 'name' => 'الإدارة العامة', 'description' => 'الإدارة العليا والتخطيط الاستراتيجي', 'sort_order' => 10],
            ['code' => 'HR', 'name' => 'الموارد البشرية', 'description' => 'شؤون الموظفين والتوظيف', 'sort_order' => 20],
            ['code' => 'SALES', 'name' => 'المبيعات', 'description' => 'إدارة المبيعات والعملاء', 'sort_order' => 30],
            ['code' => 'WH', 'name' => 'المخزون واللوجستيات', 'description' => 'إدارة المخازن والتوريد', 'sort_order' => 40],
            ['code' => 'FIN', 'name' => 'المالية', 'description' => 'المحاسبة والشؤون المالية', 'sort_order' => 50],
            ['code' => 'IT', 'name' => 'تقنية المعلومات', 'description' => 'الأنظمة والدعم التقني', 'sort_order' => 60],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['is_active' => true])
            );
        }
    }

    private function createEmployeesForAllUsers(): void
    {
        $activeStatus = EmployeeStatus::where('code', 'ACTIVE')->first();
        $contractType = ContractType::where('code', 'PERMANENT')->first();
        $contractStatus = ContractStatus::where('code', 'ACTIVE')->first();

        $deptMap = [
            'admin' => ['code' => 'GEN', 'title' => 'GEN_MGR', 'grade' => 'G4', 'scale' => 'SS-G4', 'salary' => 25000],
            'accountant' => ['code' => 'FIN', 'title' => 'ACC', 'grade' => 'G3', 'scale' => 'SS-G3', 'salary' => 15000],
            'warehouse_keeper' => ['code' => 'WH', 'title' => 'WH_KEEPER', 'grade' => 'G2', 'scale' => 'SS-G2', 'salary' => 10000],
            'sales_manager' => ['code' => 'SALES', 'title' => 'SALES_MGR', 'grade' => 'G3', 'scale' => 'SS-G3', 'salary' => 18000],
            'sales_rep' => ['code' => 'SALES', 'title' => 'SALES_REP', 'grade' => 'G2', 'scale' => 'SS-G2', 'salary' => 8000],
            'default' => ['code' => 'GEN', 'title' => 'GEN_MGR', 'grade' => 'G2', 'scale' => 'SS-G2', 'salary' => 10000],
        ];

        $empCounter = 0;

        $users = User::all();

        foreach ($users as $user) {
            if ($user->id === null) continue;

            // Skip handheld device user
            if ($user->usercode == 99999) continue;

            // Get user's primary role
            $userRole = DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $user->id)
                ->select('roles.name')
                ->first();
            $roleCode = $userRole?->name ?? 'default';
            $roleKey = $this->mapRoleToKey($roleCode);
            $config = $deptMap[$roleKey] ?? $deptMap['default'];

            $dept = Department::where('code', $config['code'])->first();
            $jobTitle = JobTitle::where('code', $config['title'])->first();
            $grade = JobGrade::where('code', $config['grade'])->first();
            $scale = SalaryScale::where('code', $config['scale'])->first();

            // Get user's branch
            $userBranch = DB::table('user_branches')
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->first();

            if (!$userBranch) {
                $userBranch = DB::table('user_branches')
                    ->where('user_id', $user->id)
                    ->orderBy('id')
                    ->first();
            }

            $branchId = $userBranch?->branch_id;

            // Determine employee name from user name
            $nameParts = explode(' ', trim($user->name));
            $firstName = $nameParts[0] ?? $user->name;
            $lastName = end($nameParts) ?? '';
            $secondName = count($nameParts) > 2 ? $nameParts[1] : '';

            $empCode = 'USR-EMP-' . str_pad($empCounter + 1, 4, '0', STR_PAD_LEFT);
            $empCounter++;

            $employee = Employee::updateOrCreate(
                ['employee_code' => $empCode],
                [
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'first_name_ar' => $firstName,
                    'second_name_ar' => $secondName,
                    'last_name_ar' => $lastName,
                    'first_name_en' => $firstName,
                    'last_name_en' => $lastName,
                    'gender' => 'male',
                    'mobile' => $user->phone ?? '010' . str_pad($user->usercode, 8, '0', STR_PAD_LEFT),
                    'email' => $user->email,
                    'birth_date' => rand(1980, 2000) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                    'marital_status' => 'married',
                    'hire_date' => '2023-01-15',
                    'employee_status_id' => $activeStatus?->id,
                    'department_id' => $dept?->id,
                    'is_active' => true,
                ]
            );

            EmployeeAssignment::updateOrCreate(
                ['employee_id' => $employee->id, 'is_current' => true],
                [
                    'branch_id' => $branchId,
                    'job_title_id' => $jobTitle?->id,
                    'job_grade_id' => $grade?->id,
                    'salary_scale_id' => $scale?->id,
                    'effective_from' => '2023-01-15',
                    'is_current' => true,
                ]
            );

            if ($contractType && $contractStatus) {
                EmployeeContract::updateOrCreate(
                    ['contract_number' => 'USR-CTR-' . str_pad($empCounter, 4, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $user->company_id,
                        'employee_id' => $employee->id,
                        'contract_type_id' => $contractType->id,
                        'contract_status_id' => $contractStatus->id,
                        'start_date' => '2023-01-15',
                        'end_date' => '2027-01-14',
                        'basic_salary' => $config['salary'],
                        'housing_allowance' => $config['salary'] * 0.2,
                        'transportation_allowance' => $config['salary'] * 0.1,
                        'other_allowances' => $config['salary'] * 0.05,
                    ]
                );
            }
        }
    }

    private function mapRoleToKey(string $roleCode): string
    {
        return match ($roleCode) {
            'admin', 'super_admin' => 'admin',
            'accountant' => 'accountant',
            'warehouse_keeper' => 'warehouse_keeper',
            'sales_manager' => 'sales_manager',
            'sales_rep' => 'sales_rep',
            default => 'default',
        };
    }
}
