<?php

namespace Database\Seeders;

use App\Models\OrganizationUnitType;
use Illuminate\Database\Seeder;

class OrganizationUnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'DEPT', 'name_ar' => 'قسم', 'name_en' => 'Department', 'sort_order' => 1, 'is_system' => true],
            ['code' => 'SECTION', 'name_ar' => 'شعبة', 'name_en' => 'Section', 'sort_order' => 2, 'is_system' => true],
            ['code' => 'UNIT', 'name_ar' => 'وحدة', 'name_en' => 'Unit', 'sort_order' => 3, 'is_system' => true],
            ['code' => 'BRANCH', 'name_ar' => 'فرع', 'name_en' => 'Branch', 'sort_order' => 4, 'is_system' => true],
            ['code' => 'DIVISION', 'name_ar' => 'إدارة', 'name_en' => 'Division', 'sort_order' => 5, 'is_system' => true],
            ['code' => 'TEAM', 'name_ar' => 'فريق', 'name_en' => 'Team', 'sort_order' => 6, 'is_system' => true],
            ['code' => 'OFFICE', 'name_ar' => 'مكتب', 'name_en' => 'Office', 'sort_order' => 7, 'is_system' => true],
            ['code' => 'GROUP', 'name_ar' => 'مجموعة', 'name_en' => 'Group', 'sort_order' => 8, 'is_system' => true],
            ['code' => 'CUSTOM', 'name_ar' => 'مخصص', 'name_en' => 'Custom', 'sort_order' => 99, 'is_system' => false],
        ];

        foreach ($types as $type) {
            OrganizationUnitType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
