<?php

namespace Database\Seeders;

use App\Models\SalaryComponentType;
use Illuminate\Database\Seeder;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'EARNING', 'name_ar' => 'عنصر راتب', 'name_en' => 'Earning'],
            ['code' => 'DEDUCTION', 'name_ar' => 'خصم', 'name_en' => 'Deduction'],
            ['code' => 'EMPLOYER_CONTRIBUTION', 'name_ar' => 'مساهمة صاحب العمل', 'name_en' => 'Employer Contribution'],
        ];

        foreach ($types as $t) {
            SalaryComponentType::updateOrCreate(['code' => $t['code']], $t);
        }
    }
}
