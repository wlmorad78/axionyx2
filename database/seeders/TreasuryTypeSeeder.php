<?php

namespace Database\Seeders;

use App\Models\TreasuryType;
use Illuminate\Database\Seeder;

class TreasuryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'REVENUE', 'name_ar' => 'ايرادات', 'name_en' => 'Revenue', 'description' => 'خزينة لتسجيل الايرادات', 'is_system' => true, 'is_active' => true],
            ['code' => 'EXPENSE', 'name_ar' => 'مصروفات', 'name_en' => 'Expense', 'description' => 'خزينة لتسجيل المصروفات', 'is_system' => true, 'is_active' => true],
            ['code' => 'OTHER', 'name_ar' => 'اخرى', 'name_en' => 'Other', 'description' => 'خزينة لاغراض اخرى', 'is_system' => false, 'is_active' => true],
        ];

        foreach ($types as $type) {
            TreasuryType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
