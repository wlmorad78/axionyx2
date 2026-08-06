<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\JobFamily;
use App\Models\JobGrade;
use App\Models\JobTitle;
use App\Models\OrganizationalLevel;
use App\Models\OrganizationUnit;
use App\Models\OrganizationUnitType;
use App\Models\SalaryScale;
use App\Models\Branch;
use App\Models\CostCenter;
use App\Models\SalesTerritory;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeContract;
use App\Models\ContractType;
use App\Models\ContractStatus;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\EmployeeLoan;
use App\Models\EmployeeAdvance;
use App\Models\EmployeePenalty;
use App\Models\EmployeeReward;
use App\Models\Shift;
use App\Models\ShiftType;
use App\Models\EmployeeShift;
use App\Models\AttendanceRecord;
use App\Models\AttendanceStatus;
use App\Models\Holiday;
use App\Models\EmployeeMission;
use Illuminate\Database\Seeder;

class HrFullSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHrStructure();
        $this->seedShifts();
        $this->seedHolidays();
        $this->seedEmployees();
    }

    private function seedHrStructure(): void
    {
        // Job Families
        $families = [
            ['code' => 'SALES', 'name_ar' => 'المبيعات', 'name_en' => 'Sales'],
            ['code' => 'FINANCE', 'name_ar' => 'المالية', 'name_en' => 'Finance'],
            ['code' => 'HR', 'name_ar' => 'الموارد البشرية', 'name_en' => 'HR'],
            ['code' => 'WAREHOUSE', 'name_ar' => 'المخازن', 'name_en' => 'Warehouse'],
            ['code' => 'PROCUREMENT', 'name_ar' => 'المشتريات', 'name_en' => 'Procurement'],
            ['code' => 'MARKETING', 'name_ar' => 'التسويق', 'name_en' => 'Marketing'],
            ['code' => 'IT', 'name_ar' => 'تقنية المعلومات', 'name_en' => 'IT'],
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

        // Job Titles
        $titles = [
            ['code' => 'SALES_REP', 'family' => 'SALES', 'name_ar' => 'مندوب مبيعات', 'name_en' => 'Sales Representative'],
            ['code' => 'SALES_SUP', 'family' => 'SALES', 'name_ar' => 'مشرف مبيعات', 'name_en' => 'Sales Supervisor'],
            ['code' => 'SALES_MGR', 'family' => 'SALES', 'name_ar' => 'مدير مبيعات', 'name_en' => 'Sales Manager'],
            ['code' => 'ACC', 'family' => 'FINANCE', 'name_ar' => 'محاسب', 'name_en' => 'Accountant'],
            ['code' => 'SR_ACC', 'family' => 'FINANCE', 'name_ar' => 'محاسب أول', 'name_en' => 'Senior Accountant'],
            ['code' => 'CHIEF_ACC', 'family' => 'FINANCE', 'name_ar' => 'رئيس المحاسبين', 'name_en' => 'Chief Accountant'],
            ['code' => 'WH_KEEPER', 'family' => 'WAREHOUSE', 'name_ar' => 'أمين مخزن', 'name_en' => 'Warehouse Keeper'],
            ['code' => 'WH_MGR', 'family' => 'WAREHOUSE', 'name_ar' => 'مدير مخزن', 'name_en' => 'Warehouse Manager'],
            ['code' => 'HR_MGR', 'family' => 'HR', 'name_ar' => 'مدير الموارد البشرية', 'name_en' => 'HR Manager'],
            ['code' => 'IT_MGR', 'family' => 'IT', 'name_ar' => 'مدير تقنية المعلومات', 'name_en' => 'IT Manager'],
            ['code' => 'MARKETING_MGR', 'family' => 'MARKETING', 'name_ar' => 'مدير التسويق', 'name_en' => 'Marketing Manager'],
            ['code' => 'PROC_MGR', 'family' => 'PROCUREMENT', 'name_ar' => 'مدير المشتريات', 'name_en' => 'Procurement Manager'],
        ];

        foreach ($titles as $t) {
            JobTitle::updateOrCreate(
                ['code' => $t['code']],
                ['company_id' => $companyId, 'job_family_id' => $famModels[$t['family']]->id, 'name_ar' => $t['name_ar'], 'name_en' => $t['name_en']]
            );
        }

        // Job Grades
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

        // Salary Scales
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

        // Employee Statuses
        $statuses = [
            ['code' => 'ACTIVE', 'name_ar' => 'نشط', 'name_en' => 'Active', 'color' => '#22C55E', 'is_system' => true],
            ['code' => 'ON_LEAVE', 'name_ar' => 'إجازة', 'name_en' => 'On Leave', 'color' => '#F59E0B', 'is_system' => true],
            ['code' => 'SUSPENDED', 'name_ar' => 'موقوف', 'name_en' => 'Suspended', 'color' => '#EF4444', 'is_system' => true],
            ['code' => 'TERMINATED', 'name_ar' => 'منتهي خدمة', 'name_en' => 'Terminated', 'color' => '#6B7280', 'is_system' => true],
            ['code' => 'RETIRED', 'name_ar' => 'متقاعد', 'name_en' => 'Retired', 'color' => '#8B5CF6', 'is_system' => true],
        ];

        foreach ($statuses as $s) {
            EmployeeStatus::updateOrCreate(['code' => $s['code']], $s);
        }
    }

    private function seedShifts(): void
    {
        $companies = Company::all();
        $morningType = ShiftType::where('code', 'MORNING')->first();
        $eveningType = ShiftType::where('code', 'EVENING')->first();

        foreach ($companies as $company) {
            Shift::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'SHIFT-MORNING-' . $company->id],
                [
                    'shift_type_id' => $morningType?->id,
                    'name_ar' => 'الدوام الصباحي',
                    'name_en' => 'Morning Shift',
                    'start_time' => '08:00',
                    'end_time' => '17:00',
                    'break_minutes' => 60,
                    'grace_in_minutes' => 15,
                    'grace_out_minutes' => 15,
                    'is_active' => true,
                ]
            );

            Shift::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'SHIFT-EVENING-' . $company->id],
                [
                    'shift_type_id' => $eveningType?->id,
                    'name_ar' => 'الدوام المسائي',
                    'name_en' => 'Evening Shift',
                    'start_time' => '14:00',
                    'end_time' => '23:00',
                    'break_minutes' => 60,
                    'grace_in_minutes' => 15,
                    'grace_out_minutes' => 15,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedHolidays(): void
    {
        $companies = Company::all();
        $holidays = [
            ['name_ar' => 'عيد الفطر المبارك', 'name_en' => 'Eid Al-Fitr', 'is_paid' => true],
            ['name_ar' => 'عيد الأضحى المبارك', 'name_en' => 'Eid Al-Adha', 'is_paid' => true],
            ['name_ar' => 'عيد مصر القومي', 'name_en' => 'National Day', 'is_paid' => true],
            ['name_ar' => 'شم النسيم', 'name_en' => 'Sham El-Nessim', 'is_paid' => true],
            ['name_ar' => 'ثورة 23 يوليو', 'name_en' => 'July 23 Revolution', 'is_paid' => true],
            ['name_ar' => 'عيد العمال', 'name_en' => 'Labour Day', 'is_paid' => true],
            ['name_ar' => 'الكريسماس', 'name_en' => 'Christmas', 'is_paid' => true],
        ];

        $year = date('Y');
        $holidayDates = [
            "{$year}-04-10", "{$year}-06-17", "{$year}-07-23",
            "{$year}-04-25", "{$year}-07-23", "{$year}-05-01",
            "{$year}-12-25",
        ];

        foreach ($companies as $company) {
            foreach ($holidays as $i => $h) {
                Holiday::updateOrCreate(
                    ['company_id' => $company->id, 'name_ar' => $h['name_ar']],
                    [
                        'name_en' => $h['name_en'],
                        'holiday_date' => $holidayDates[$i] ?? "{$year}-01-01",
                        'is_paid' => $h['is_paid'],
                    ]
                );
            }
        }
    }

    private function seedEmployees(): void
    {
        $companies = Company::all();
        $activeStatus = EmployeeStatus::where('code', 'ACTIVE')->first();
        $jobTitleSales = JobTitle::where('code', 'SALES_REP')->first();
        $jobTitleAcc = JobTitle::where('code', 'ACC')->first();
        $jobTitleWh = JobTitle::where('code', 'WH_KEEPER')->first();
        $gradeG2 = JobGrade::where('code', 'G2')->first();
        $scaleG2 = SalaryScale::where('code', 'SS-G2')->first();

        $employeesData = [
            ['first_name_ar' => 'أحمد', 'second_name_ar' => 'محمد', 'last_name_ar' => 'علي', 'first_name_en' => 'Ahmed', 'last_name_en' => 'Ali', 'gender' => 'male', 'mobile' => '01012345678', 'job_title' => 'SALES_REP'],
            ['first_name_ar' => 'فاطمة', 'second_name_ar' => 'حسن', 'last_name_ar' => 'إبراهيم', 'first_name_en' => 'Fatma', 'last_name_en' => 'Ibrahim', 'gender' => 'female', 'mobile' => '01023456789', 'job_title' => 'ACC'],
            ['first_name_ar' => 'محمد', 'second_name_ar' => 'عبدالله', 'last_name_ar' => 'خالد', 'first_name_en' => 'Mohamed', 'last_name_en' => 'Khaled', 'gender' => 'male', 'mobile' => '01034567890', 'job_title' => 'WH_KEEPER'],
            ['first_name_ar' => 'سارة', 'second_name_ar' => 'أحمد', 'last_name_ar' => 'محمود', 'first_name_en' => 'Sarah', 'last_name_en' => 'Mahmoud', 'gender' => 'female', 'mobile' => '01045678901', 'job_title' => 'SALES_REP'],
            ['first_name_ar' => 'عمر', 'second_name_ar' => 'حسين', 'last_name_ar' => 'إبراهيم', 'first_name_en' => 'Omar', 'last_name_en' => 'Ibrahim', 'gender' => 'male', 'mobile' => '01056789012', 'job_title' => 'SALES_REP'],
            ['first_name_ar' => 'نور', 'second_name_ar' => 'محمد', 'last_name_ar' => 'حسن', 'first_name_en' => 'Nour', 'last_name_en' => 'Hassan', 'gender' => 'female', 'mobile' => '01067890123', 'job_title' => 'SALES_REP'],
            ['first_name_ar' => 'خالد', 'second_name_ar' => 'عادل', 'last_name_ar' => 'محمود', 'first_name_en' => 'Khaled', 'last_name_en' => 'Mahmoud', 'gender' => 'male', 'mobile' => '01078901234', 'job_title' => 'SALES_REP'],
            ['first_name_ar' => 'مريم', 'second_name_ar' => 'علي', 'last_name_ar' => 'محمود', 'first_name_en' => 'Mariam', 'last_name_en' => 'Mahmoud', 'gender' => 'female', 'mobile' => '01089012345', 'job_title' => 'SALES_REP'],
        ];

        foreach ($companies as $company) {
            $branch = Branch::where('company_id', $company->id)->first();

            foreach ($employeesData as $i => $emp) {
                $employee = Employee::updateOrCreate(
                    ['employee_code' => 'EMP-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $company->id,
                        'first_name_ar' => $emp['first_name_ar'],
                        'second_name_ar' => $emp['second_name_ar'],
                        'last_name_ar' => $emp['last_name_ar'],
                        'first_name_en' => $emp['first_name_en'],
                        'last_name_en' => $emp['last_name_en'],
                        'gender' => $emp['gender'],
                        'mobile' => $emp['mobile'],
                        'birth_date' => rand(1980, 2000) . '-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT),
                        'marital_status' => $i % 2 === 0 ? 'married' : 'single',
                        'hire_date' => '2023-01-15',
                        'employee_status_id' => $activeStatus?->id,
                        'is_active' => true,
                    ]
                );

                // Employee Assignment
                $jobTitle = match ($emp['job_title']) {
                    'SALES_REP' => $jobTitleSales,
                    'ACC' => $jobTitleAcc,
                    'WH_KEEPER' => $jobTitleWh,
                    default => $jobTitleSales,
                };

                EmployeeAssignment::updateOrCreate(
                    ['employee_id' => $employee->id, 'is_current' => true],
                    [
                        'branch_id' => $branch?->id,
                        'job_title_id' => $jobTitle?->id,
                        'job_grade_id' => $gradeG2?->id,
                        'salary_scale_id' => $scaleG2?->id,
                        'effective_from' => '2023-01-15',
                        'is_current' => true,
                    ]
                );

                // Contract
                $contractType = ContractType::where('code', 'PERMANENT')->first();
                $contractStatus = ContractStatus::where('code', 'ACTIVE')->first();

                if ($contractType && $contractStatus) {
                    EmployeeContract::updateOrCreate(
                        ['contract_number' => 'CTR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                        [
                            'company_id' => $company->id,
                            'employee_id' => $employee->id,
                            'contract_type_id' => $contractType->id,
                            'contract_status_id' => $contractStatus->id,
                            'start_date' => '2023-01-15',
                            'end_date' => '2026-01-14',
                            'basic_salary' => 8000,
                            'housing_allowance' => 2000,
                            'transportation_allowance' => 1000,
                            'other_allowances' => 500,
                        ]
                    );
                }

                // Leave Requests
                $annualLeave = LeaveType::where('code', 'ANNUAL')->first();
                if ($annualLeave && $i < 3) {
                    LeaveRequest::updateOrCreate(
                        ['employee_id' => $employee->id, 'from_date' => '2026-03-01'],
                        [
                            'leave_type_id' => $annualLeave->id,
                            'to_date' => '2026-03-05',
                            'days_count' => 5,
                            'reason' => 'إجازة سنوية',
                            'status' => 'approved',
                        ]
                    );
                }

                // Loans
                if ($i < 2) {
                    EmployeeLoan::updateOrCreate(
                        ['loan_number' => 'LN-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                        [
                            'employee_id' => $employee->id,
                            'amount' => 10000,
                            'installments_count' => 10,
                            'monthly_installment' => 1000,
                            'start_date' => '2026-01-01',
                            'status' => 'active',
                        ]
                    );
                }

                // Advances
                if ($i < 3) {
                    EmployeeAdvance::updateOrCreate(
                        ['advance_number' => 'ADV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                        [
                            'employee_id' => $employee->id,
                            'amount' => 3000,
                            'request_date' => '2026-02-15',
                            'status' => 'approved',
                        ]
                    );
                }

                // Penalties
                if ($i === 3) {
                    EmployeePenalty::create([
                        'employee_id' => $employee->id,
                        'penalty_date' => '2026-04-10',
                        'amount' => 200,
                        'reason' => 'تأخر عن الدوام',
                    ]);
                }

                // Rewards
                if ($i === 0) {
                    EmployeeReward::create([
                        'employee_id' => $employee->id,
                        'reward_date' => '2026-03-20',
                        'amount' => 1500,
                        'reason' => 'أفضل مندوب مبيعات للشهر',
                    ]);
                }

                // Shift assignment
                $morningShift = Shift::where('code', 'SHIFT-MORNING-' . $company->id)->first();
                if ($morningShift) {
                    EmployeeShift::updateOrCreate(
                        ['employee_id' => $employee->id, 'is_current' => true],
                        [
                            'shift_id' => $morningShift->id,
                            'effective_from' => '2023-01-15',
                            'is_current' => true,
                        ]
                    );
                }

                // Missions
                if ($i === 4) {
                    EmployeeMission::create([
                        'employee_id' => $employee->id,
                        'from_date' => '2026-05-01',
                        'to_date' => '2026-05-05',
                        'destination' => 'الرياض',
                        'reason' => 'حضور مؤتمر المبيعات',
                        'status' => 'approved',
                    ]);
                }
            }
        }
    }
}
