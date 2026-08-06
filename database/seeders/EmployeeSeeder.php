<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'employee_code' => 'EMP-100001',
                'first_name_ar' => 'أحمد',
                'second_name_ar' => 'محمد',
                'last_name_ar' => 'علي',
                'first_name_en' => 'Ahmed',
                'second_name_en' => 'Mohamed',
                'last_name_en' => 'Ali',
                'gender' => 'male',
                'mobile' => '01000000001',
                'is_active' => true,
            ],
            [
                'employee_code' => 'EMP-100002',
                'first_name_ar' => 'فاطمة',
                'second_name_ar' => 'حسن',
                'last_name_ar' => 'إbrahim',
                'first_name_en' => 'Fatma',
                'second_name_en' => 'Hassan',
                'last_name_en' => 'Ibrahim',
                'gender' => 'female',
                'mobile' => '01000000002',
                'is_active' => true,
            ],
            [
                'employee_code' => 'EMP-100003',
                'first_name_ar' => 'محمد',
                'second_name_ar' => 'عبدالله',
                'last_name_ar' => 'خالد',
                'first_name_en' => 'Mohamed',
                'second_name_en' => 'Abdullah',
                'last_name_en' => 'Khaled',
                'gender' => 'male',
                'mobile' => '01000000003',
                'is_active' => true,
            ],
            [
                'employee_code' => 'EMP-100004',
                'first_name_ar' => 'سارة',
                'second_name_ar' => 'أحمد',
                'last_name_ar' => 'محمود',
                'first_name_en' => 'Sarah',
                'second_name_en' => 'Ahmed',
                'last_name_en' => 'Mahmoud',
                'gender' => 'female',
                'mobile' => '01000000004',
                'is_active' => true,
            ],
        ];

        foreach ($employees as $data) {
            Employee::updateOrCreate(
                ['employee_code' => $data['employee_code']],
                $data
            );
        }
    }
}
