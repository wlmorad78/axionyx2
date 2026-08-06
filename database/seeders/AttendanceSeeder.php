<?php

namespace Database\Seeders;

use App\Models\ShiftType;
use App\Models\AttendanceStatus;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $shiftTypes = [
            ['code' => 'MORNING', 'name_ar' => 'صباحي', 'name_en' => 'Morning'],
            ['code' => 'EVENING', 'name_ar' => 'مسائي', 'name_en' => 'Evening'],
            ['code' => 'NIGHT', 'name_ar' => 'ليلي', 'name_en' => 'Night'],
            ['code' => 'FLEXIBLE', 'name_ar' => 'مرنن', 'name_en' => 'Flexible'],
        ];

        foreach ($shiftTypes as $st) {
            ShiftType::updateOrCreate(['code' => $st['code']], $st);
        }

        $statuses = [
            ['code' => 'PRESENT', 'name_ar' => 'حاضر', 'name_en' => 'Present', 'color' => '#22C55E', 'is_system' => true],
            ['code' => 'LATE', 'name_ar' => 'متأخر', 'name_en' => 'Late', 'color' => '#F59E0B', 'is_system' => true],
            ['code' => 'ABSENT', 'name_ar' => 'غائب', 'name_en' => 'Absent', 'color' => '#EF4444', 'is_system' => true],
            ['code' => 'LEAVE', 'name_ar' => 'إجازة', 'name_en' => 'Leave', 'color' => '#3B82F6', 'is_system' => true],
            ['code' => 'HOLIDAY', 'name_ar' => 'عطلة', 'name_en' => 'Holiday', 'color' => '#8B5CF6', 'is_system' => true],
            ['code' => 'MISSION', 'name_ar' => 'مأمورية', 'name_en' => 'Mission', 'color' => '#0EA5E9', 'is_system' => true],
        ];

        foreach ($statuses as $s) {
            AttendanceStatus::updateOrCreate(['code' => $s['code']], $s);
        }
    }
}