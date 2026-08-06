<?php

namespace Database\Seeders;

use App\Models\PositionLevel;
use Illuminate\Database\Seeder;

class PositionLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'REPUBLIC', 'name' => 'مدير جمهورية', 'sort_order' => 10, 'description' => 'أعلى مستوى إداري على مستوى الجمهورية'],
            ['code' => 'RETAIL_REP', 'name' => 'مدير تجزئة جمهورية', 'sort_order' => 20, 'description' => 'إدارة تجزئة على مستوى الجمهورية'],
            ['code' => 'REGIONAL', 'name' => 'مدير إقليمي', 'sort_order' => 30, 'description' => 'إدارة إقليم جغرافي'],
            ['code' => 'MANAGER', 'name' => 'مدير', 'sort_order' => 40, 'description' => 'مدير فرع أو وحدة'],
            ['code' => 'SUPERVISOR', 'name' => 'مشرف', 'sort_order' => 50, 'description' => 'إشراف مباشر على الفريق'],
            ['code' => 'REP', 'name' => 'مندوب', 'sort_order' => 60, 'description' => 'مندوب مبيعات ميداني'],
            ['code' => 'KEEPER', 'name' => 'أمين مخزن', 'sort_order' => 70, 'description' => 'مسؤول عن عمليات المخزن اليومية'],
            ['code' => 'WORKER', 'name' => 'عامل', 'sort_order' => 80, 'description' => 'عامل تنفيذي في المخزن أو الإنتاج'],
            ['code' => 'STAFF', 'name' => 'موظف', 'sort_order' => 90, 'description' => 'موظف عام في الإدارات الداعمة'],
        ];

        foreach ($levels as $level) {
            PositionLevel::updateOrCreate(
                ['code' => $level['code']],
                array_merge($level, ['is_active' => true])
            );
        }
    }
}
