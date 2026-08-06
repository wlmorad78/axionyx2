<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'GEN', 'name' => 'الإدارة العامة', 'description' => 'الإدارة العليا والتخطيط الاستراتيجي', 'sort_order' => 10],
            ['code' => 'HR', 'name' => 'الموارد البشرية', 'description' => 'شؤون الموظفين والتوظيف', 'sort_order' => 20],
            ['code' => 'SALES', 'name' => 'المبيعات', 'description' => 'إدارة المبيعات والعملاء', 'sort_order' => 30],
            ['code' => 'WH', 'name' => 'المخزون واللوجستيات', 'description' => 'إدارة المخازن والتوريد', 'sort_order' => 40],
            ['code' => 'FIN', 'name' => 'المالية', 'description' => 'المحاسبة والشؤون المالية', 'sort_order' => 50],
            ['code' => 'IT', 'name' => 'تقنية المعلومات', 'description' => 'الأنظمة والدعم التقني', 'sort_order' => 60],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                array_merge($department, ['is_active' => true])
            );
        }
    }
}
