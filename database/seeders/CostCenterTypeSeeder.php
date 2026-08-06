<?php

namespace Database\Seeders;

use App\Models\CostCenterType;
use Illuminate\Database\Seeder;

class CostCenterTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'REVENUE', 'name_ar' => 'مركز إيراد', 'name_en' => 'Revenue Center', 'is_system' => true],
            ['code' => 'EXPENSE', 'name_ar' => 'مركز مصروف', 'name_en' => 'Expense Center', 'is_system' => true],
            ['code' => 'OPERATION', 'name_ar' => 'تشغيلي', 'name_en' => 'Operation Center', 'is_system' => true],
            ['code' => 'ADMIN', 'name_ar' => 'إداري', 'name_en' => 'Admin Center', 'is_system' => true],
        ];

        foreach ($types as $type) {
            CostCenterType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
