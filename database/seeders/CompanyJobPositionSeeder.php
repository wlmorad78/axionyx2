<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\PositionLevel;
use Illuminate\Database\Seeder;

class CompanyJobPositionSeeder extends Seeder
{
    public function run(): void
    {
        $levels = PositionLevel::pluck('id', 'code');

        $companies = Company::all();

        foreach ($companies as $company) {
            $departments = Department::where('company_id', $company->id)->get();

            foreach ($departments as $department) {
                $this->createPositionsForDepartment($department, $levels);
            }
        }
    }

    private function createPositionsForDepartment(Department $department, $levels): void
    {
        $positions = match ($department->code) {
            'GEN' => $this->getGeneralPositions(),
            'HR' => $this->getHRPositions(),
            'FIN' => $this->getFinancePositions(),
            'SALES' => $this->getSalesPositions(),
            'WH' => $this->getWarehousePositions(),
            'IT' => $this->getITPositions(),
            default => [],
        };

        $created = [];

        foreach ($positions as $position) {
            $parentId = null;
            if ($position['parent'] && isset($created[$position['parent']])) {
                $parentId = $created[$position['parent']];
            }

            $code = "{$department->code}-{$position['code']}-{$department->company_id}";

            $jobPosition = JobPosition::updateOrCreate(
                ['code' => $code],
                [
                    'department_id' => $department->id,
                    'parent_id' => $parentId,
                    'position_level_id' => $levels[$position['level']] ?? null,
                    'name' => $position['name'],
                    'sort_order' => $position['sort'],
                    'description' => "وظيفة {$position['name']} في {$department->name}",
                    'is_active' => true,
                ]
            );

            $created[$position['code']] = $jobPosition->id;
        }
    }

    private function getGeneralPositions(): array
    {
        return [
            ['code' => 'CEO', 'name' => 'الرئيس التنفيذي', 'level' => 'REPUBLIC', 'parent' => null, 'sort' => 1],
            ['code' => 'GM', 'name' => 'المدير العام', 'level' => 'REGIONAL', 'parent' => 'CEO', 'sort' => 2],
            ['code' => 'MGR', 'name' => 'مدير عام', 'level' => 'MANAGER', 'parent' => 'GM', 'sort' => 3],
            ['code' => 'SUP', 'name' => 'مشرف إداري', 'level' => 'SUPERVISOR', 'parent' => 'MGR', 'sort' => 4],
            ['code' => 'SEC', 'name' => 'سكرتير تنفيذي', 'level' => 'STAFF', 'parent' => 'CEO', 'sort' => 5],
            ['code' => 'EMP', 'name' => 'موظف إداري', 'level' => 'STAFF', 'parent' => 'SUP', 'sort' => 6],
        ];
    }

    private function getHRPositions(): array
    {
        return [
            ['code' => 'DIR', 'name' => 'مدير الموارد البشرية', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
            ['code' => 'REC-MGR', 'name' => 'مدير التوظيف', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 2],
            ['code' => 'REC-Off', 'name' => 'مسؤول التوظيف', 'level' => 'SUPERVISOR', 'parent' => 'REC-MGR', 'sort' => 3],
            ['code' => 'REC-EMP', 'name' => 'موظف توظيف', 'level' => 'STAFF', 'parent' => 'REC-Off', 'sort' => 4],
            ['code' => 'TRAIN-MGR', 'name' => 'مدير التدريب والتطوير', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 5],
            ['code' => 'TRAIN-EMP', 'name' => 'موظف تدريب', 'level' => 'STAFF', 'parent' => 'TRAIN-MGR', 'sort' => 6],
            ['code' => 'AFF-MGR', 'name' => 'مدير شؤون الموظفين', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 7],
            ['code' => 'AFF-EMP', 'name' => 'موظف شؤون موظفين', 'level' => 'STAFF', 'parent' => 'AFF-MGR', 'sort' => 8],
        ];
    }

    private function getFinancePositions(): array
    {
        return [
            ['code' => 'DIR', 'name' => 'المدير المالي', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
            ['code' => 'ACC-MGR', 'name' => 'مدير المحاسبة', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 2],
            ['code' => 'ACC-SUP', 'name' => 'مشرف حسابات', 'level' => 'SUPERVISOR', 'parent' => 'ACC-MGR', 'sort' => 3],
            ['code' => 'ACC-EMP', 'name' => 'محاسب', 'level' => 'STAFF', 'parent' => 'ACC-SUP', 'sort' => 4],
            ['code' => 'AR-MGR', 'name' => 'مدير الذمم المدينة', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 5],
            ['code' => 'AR-EMP', 'name' => 'موظف ذمم مدينة', 'level' => 'STAFF', 'parent' => 'AR-MGR', 'sort' => 6],
            ['code' => 'AP-MGR', 'name' => 'مدير الذمم الدائنة', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 7],
            ['code' => 'AP-EMP', 'name' => 'موظف ذمم دائنة', 'level' => 'STAFF', 'parent' => 'AP-MGR', 'sort' => 8],
        ];
    }

    private function getSalesPositions(): array
    {
        return [
            ['code' => 'DIR', 'name' => 'مدير المبيعات', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
            ['code' => 'INSIDE-MGR', 'name' => 'مدير المبيعات الداخلية', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 2],
            ['code' => 'INSIDE-SUP', 'name' => 'مشرف مبيعات داخلية', 'level' => 'SUPERVISOR', 'parent' => 'INSIDE-MGR', 'sort' => 3],
            ['code' => 'INSIDE-REP', 'name' => 'مندوب مبيعات داخلية', 'level' => 'REP', 'parent' => 'INSIDE-SUP', 'sort' => 4],
            ['code' => 'FIELD-MGR', 'name' => 'مدير المبيعات الميدانية', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 5],
            ['code' => 'FIELD-SUP', 'name' => 'مشرف مبيعات ميدانية', 'level' => 'SUPERVISOR', 'parent' => 'FIELD-MGR', 'sort' => 6],
            ['code' => 'FIELD-REP', 'name' => 'مندوب مبيعات ميداني', 'level' => 'REP', 'parent' => 'FIELD-SUP', 'sort' => 7],
            ['code' => 'TEAM1-LDR', 'name' => 'قائد فريق مبيعات 1', 'level' => 'SUPERVISOR', 'parent' => 'DIR', 'sort' => 8],
            ['code' => 'TEAM1-REP', 'name' => 'مندوب فريق مبيعات 1', 'level' => 'REP', 'parent' => 'TEAM1-LDR', 'sort' => 9],
            ['code' => 'TEAM2-LDR', 'name' => 'قائد فريق مبيعات 2', 'level' => 'SUPERVISOR', 'parent' => 'DIR', 'sort' => 10],
            ['code' => 'TEAM2-REP', 'name' => 'مندوب فريق مبيعات 2', 'level' => 'REP', 'parent' => 'TEAM2-LDR', 'sort' => 11],
        ];
    }

    private function getWarehousePositions(): array
    {
        return [
            ['code' => 'DIR', 'name' => 'مدير المخازن واللوجستيات', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
            ['code' => 'MAIN-MGR', 'name' => 'مدير المخزن الرئيسي', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 2],
            ['code' => 'MAIN-SUP', 'name' => 'مشرف مخزن رئيسي', 'level' => 'SUPERVISOR', 'parent' => 'MAIN-MGR', 'sort' => 3],
            ['code' => 'MAIN-KPR', 'name' => 'أمين مخزن رئيسي', 'level' => 'KEEPER', 'parent' => 'MAIN-SUP', 'sort' => 4],
            ['code' => 'MAIN-WRK', 'name' => 'عامل مخزن رئيسي', 'level' => 'WORKER', 'parent' => 'MAIN-KPR', 'sort' => 5],
            ['code' => 'RET-MGR', 'name' => 'مدير مخزن المرتجعات', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 6],
            ['code' => 'RET-KPR', 'name' => 'أمين مخزن مرتجعات', 'level' => 'KEEPER', 'parent' => 'RET-MGR', 'sort' => 7],
            ['code' => 'INV-SUP', 'name' => 'مشرف الجرد والمطابقة', 'level' => 'SUPERVISOR', 'parent' => 'DIR', 'sort' => 8],
            ['code' => 'INV-EMP', 'name' => 'موظف جرد', 'level' => 'STAFF', 'parent' => 'INV-SUP', 'sort' => 9],
        ];
    }

    private function getITPositions(): array
    {
        return [
            ['code' => 'DIR', 'name' => 'مدير تقنية المعلومات', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
            ['code' => 'SYS-MGR', 'name' => 'مدير الأنظمة والشبكات', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 2],
            ['code' => 'SYS-ENG', 'name' => 'مهندس شبكات', 'level' => 'SUPERVISOR', 'parent' => 'SYS-MGR', 'sort' => 3],
            ['code' => 'SYS-EMP', 'name' => 'فني أنظمة', 'level' => 'STAFF', 'parent' => 'SYS-ENG', 'sort' => 4],
            ['code' => 'SUP-MGR', 'name' => 'مدير الدعم الفني', 'level' => 'MANAGER', 'parent' => 'DIR', 'sort' => 5],
            ['code' => 'SUP-EMP', 'name' => 'فني دعم فني', 'level' => 'STAFF', 'parent' => 'SUP-MGR', 'sort' => 6],
        ];
    }
}
