<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobFamily;
use App\Models\JobTitle;
use App\Models\JobGrade;
use App\Models\SalaryScale;
use App\Models\EmployeeStatus;
use Illuminate\Database\Seeder;

class HrStructureSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = Company::first()?->id ?? 1;

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
            ['code' => 'SR_ACC', 'family' => 'FINANCE', 'name_ar' => 'محاسب أول', 'name_en' => 'Senior Accountant'],
            ['code' => 'CHIEF_ACC', 'family' => 'FINANCE', 'name_ar' => 'رئيس المحاسبين', 'name_en' => 'Chief Accountant'],
            ['code' => 'WH_KEEPER', 'family' => 'WAREHOUSE', 'name_ar' => ' أمين مخزن', 'name_en' => 'Warehouse Keeper'],
            ['code' => 'WH_MGR', 'family' => 'WAREHOUSE', 'name_ar' => 'مدير مخزن', 'name_en' => 'Warehouse Manager'],
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
}
