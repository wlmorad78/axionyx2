<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\ContractStatus;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class EmployeeContractSeeder extends Seeder
{
    public function run(): void
    {
        $contractTypes = [
            ['code' => 'PERMANENT', 'name_ar' => 'دائم', 'name_en' => 'Permanent', 'is_system' => true],
            ['code' => 'TEMPORARY', 'name_ar' => 'مؤقت', 'name_en' => 'Temporary', 'is_system' => true],
            ['code' => 'PROBATION', 'name_ar' => 'تحت الاختبار', 'name_en' => 'Probation', 'is_system' => true],
            ['code' => 'PART_TIME', 'name_ar' => 'دوام جزئي', 'name_en' => 'Part Time', 'is_system' => true],
            ['code' => 'FREELANCE', 'name_ar' => 'متعاقد', 'name_en' => 'Freelance', 'is_system' => true],
            ['code' => 'SEASONAL', 'name_ar' => 'موسمي', 'name_en' => 'Seasonal', 'is_system' => true],
        ];

        foreach ($contractTypes as $ct) {
            ContractType::updateOrCreate(['code' => $ct['code']], $ct);
        }

        $contractStatuses = [
            ['code' => 'DRAFT', 'name_ar' => 'مسودة', 'name_en' => 'Draft', 'color' => '#6B7280', 'is_system' => true],
            ['code' => 'ACTIVE', 'name_ar' => 'نشط', 'name_en' => 'Active', 'color' => '#22C55E', 'is_system' => true],
            ['code' => 'EXPIRED', 'name_ar' => 'منتهي', 'name_en' => 'Expired', 'color' => '#F59E0B', 'is_system' => true],
            ['code' => 'TERMINATED', 'name_ar' => 'منفسخ', 'name_en' => 'Terminated', 'color' => '#EF4444', 'is_system' => true],
            ['code' => 'CANCELLED', 'name_ar' => 'ملغي', 'name_en' => 'Cancelled', 'color' => '#9CA3AF', 'is_system' => true],
        ];

        foreach ($contractStatuses as $cs) {
            ContractStatus::updateOrCreate(['code' => $cs['code']], $cs);
        }

        $leaveTypes = [
            ['code' => 'ANNUAL', 'name_ar' => 'إجازة سنوية', 'name_en' => 'Annual', 'default_days' => 21, 'is_paid' => true],
            ['code' => 'SICK', 'name_ar' => 'إجازة مرضية', 'name_en' => 'Sick', 'default_days' => 15, 'is_paid' => true],
            ['code' => 'EMERGENCY', 'name_ar' => 'إجازة طارئة', 'name_en' => 'Emergency', 'default_days' => 5, 'is_paid' => true],
            ['code' => 'MATERNITY', 'name_ar' => 'إجازة أمومة', 'name_en' => 'Maternity', 'default_days' => 90, 'is_paid' => true],
            ['code' => 'UNPAID', 'name_ar' => 'إجازة بدون راتب', 'name_en' => 'Unpaid', 'default_days' => 0, 'is_paid' => false],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::updateOrCreate(['code' => $lt['code']], $lt);
        }
    }
}
