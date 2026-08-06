<?php

namespace Database\Seeders;

use App\Models\OrganizationalLevel;
use Illuminate\Database\Seeder;

class OrganizationalLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'CEO', 'name_ar' => 'الرئيس التنفيذي', 'name_en' => 'CEO', 'level_order' => 1, 'is_system' => true],
            ['code' => 'GM', 'name_ar' => 'المدير العام', 'name_en' => 'General Manager', 'level_order' => 2, 'is_system' => true],
            ['code' => 'DIRECTOR', 'name_ar' => 'المدير', 'name_en' => 'Director', 'level_order' => 3, 'is_system' => true],
            ['code' => 'REGIONAL_MANAGER', 'name_ar' => 'مدير المنطقة', 'name_en' => 'Regional Manager', 'level_order' => 4, 'is_system' => true],
            ['code' => 'AREA_MANAGER', 'name_ar' => 'مدير المنطقة الفرعية', 'name_en' => 'Area Manager', 'level_order' => 5, 'is_system' => true],
            ['code' => 'MANAGER', 'name_ar' => 'مدير', 'name_en' => 'Manager', 'level_order' => 6, 'is_system' => true],
            ['code' => 'SUPERVISOR', 'name_ar' => 'مشرف', 'name_en' => 'Supervisor', 'level_order' => 7, 'is_system' => true],
            ['code' => 'TEAM_LEADER', 'name_ar' => 'قائد الفريق', 'name_en' => 'Team Leader', 'level_order' => 8, 'is_system' => true],
            ['code' => 'EMPLOYEE', 'name_ar' => 'موظف', 'name_en' => 'Employee', 'level_order' => 9, 'is_system' => true],
        ];

        foreach ($levels as $level) {
            OrganizationalLevel::updateOrCreate(
                ['code' => $level['code']],
                $level
            );
        }
    }
}
