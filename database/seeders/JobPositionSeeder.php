<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\JobPosition;
use App\Models\PositionLevel;
use Illuminate\Database\Seeder;

class JobPositionSeeder extends Seeder
{
    public function run(): void
    {
        JobPosition::whereNull('department_id')
            ->whereIn('code', ['MGR', 'ACC', 'SALES', 'WH', 'HR', 'IT'])
            ->forceDelete();

        $levels = PositionLevel::pluck('id', 'code');

        $structure = [
            'GEN' => [
                ['code' => 'GEN-MGR', 'name' => 'مدير عام', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
                ['code' => 'GEN-SUP', 'name' => 'مشرف إداري', 'level' => 'SUPERVISOR', 'parent' => 'GEN-MGR', 'sort' => 2],
                ['code' => 'GEN-EMP', 'name' => 'موظف إداري', 'level' => 'STAFF', 'parent' => 'GEN-SUP', 'sort' => 3],
            ],
            'HR' => [
                ['code' => 'HR-MGR', 'name' => 'مدير الموارد البشرية', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
                ['code' => 'HR-SUP', 'name' => 'مشرف موارد بشرية', 'level' => 'SUPERVISOR', 'parent' => 'HR-MGR', 'sort' => 2],
                ['code' => 'HR-EMP', 'name' => 'موظف موارد بشرية', 'level' => 'STAFF', 'parent' => 'HR-SUP', 'sort' => 3],
            ],
            'SALES' => [
                ['code' => 'SALES-REP-MGR', 'name' => 'مدير جمهورية مبيعات', 'level' => 'REPUBLIC', 'parent' => null, 'sort' => 1],
                ['code' => 'SALES-RETAIL-MGR', 'name' => 'مدير تجزئة جمهورية', 'level' => 'RETAIL_REP', 'parent' => 'SALES-REP-MGR', 'sort' => 2],
                ['code' => 'SALES-REGION-MGR', 'name' => 'مدير إقليمي مبيعات', 'level' => 'REGIONAL', 'parent' => 'SALES-RETAIL-MGR', 'sort' => 3],
                ['code' => 'SALES-BRANCH-MGR', 'name' => 'مدير فرع مبيعات', 'level' => 'MANAGER', 'parent' => 'SALES-REGION-MGR', 'sort' => 4],
                ['code' => 'SALES-SUP', 'name' => 'مشرف مبيعات', 'level' => 'SUPERVISOR', 'parent' => 'SALES-BRANCH-MGR', 'sort' => 5],
                ['code' => 'SALES-REP', 'name' => 'مندوب مبيعات', 'level' => 'REP', 'parent' => 'SALES-SUP', 'sort' => 6],
            ],
            'WH' => [
                ['code' => 'WH-MGR', 'name' => 'مدير المخزون', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
                ['code' => 'WH-SUP', 'name' => 'مشرف مخزن', 'level' => 'SUPERVISOR', 'parent' => 'WH-MGR', 'sort' => 2],
                ['code' => 'WH-KEEPER', 'name' => 'أمين مخزن', 'level' => 'KEEPER', 'parent' => 'WH-SUP', 'sort' => 3],
                ['code' => 'WH-WORKER', 'name' => 'عامل مخزن', 'level' => 'WORKER', 'parent' => 'WH-KEEPER', 'sort' => 4],
            ],
            'FIN' => [
                ['code' => 'FIN-MGR', 'name' => 'مدير مالي', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
                ['code' => 'FIN-SUP', 'name' => 'مشرف حسابات', 'level' => 'SUPERVISOR', 'parent' => 'FIN-MGR', 'sort' => 2],
                ['code' => 'FIN-ACC', 'name' => 'محاسب', 'level' => 'STAFF', 'parent' => 'FIN-SUP', 'sort' => 3],
            ],
            'IT' => [
                ['code' => 'IT-MGR', 'name' => 'مدير تقنية المعلومات', 'level' => 'MANAGER', 'parent' => null, 'sort' => 1],
                ['code' => 'IT-SUP', 'name' => 'مشرف تقنية', 'level' => 'SUPERVISOR', 'parent' => 'IT-MGR', 'sort' => 2],
                ['code' => 'IT-EMP', 'name' => 'موظف تقنية معلومات', 'level' => 'STAFF', 'parent' => 'IT-SUP', 'sort' => 3],
            ],
        ];

        $created = [];

        foreach ($structure as $departmentCode => $positions) {
            $department = Department::where('code', $departmentCode)->first();

            if (! $department) {
                continue;
            }

            foreach ($positions as $position) {
                $parentId = null;

                if ($position['parent']) {
                    $parentId = $created[$position['parent']]
                        ?? JobPosition::where('code', $position['parent'])->value('id');
                }

                $jobPosition = JobPosition::updateOrCreate(
                    ['code' => $position['code']],
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

        JobPosition::where('code', 'WH-EMP')->forceDelete();
    }
}
