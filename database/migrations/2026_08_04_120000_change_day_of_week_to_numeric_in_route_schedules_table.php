<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $mapping = [
            'Saturday'  => '1',
            'Sunday'    => '2',
            'Monday'    => '3',
            'Tuesday'   => '4',
            'Wednesday' => '5',
            'Thursday'  => '6',
            'Friday'    => '7',
        ];

        foreach ($mapping as $name => $num) {
            DB::table('route_schedules')
                ->where('day_of_week', $name)
                ->update(['day_of_week' => $num]);
        }

        // Column is already VARCHAR(20), no ALTER needed for SQLite
    }

    public function down(): void
    {
        $mapping = [
            '1' => 'Saturday',
            '2' => 'Sunday',
            '3' => 'Monday',
            '4' => 'Tuesday',
            '5' => 'Wednesday',
            '6' => 'Thursday',
            '7' => 'Friday',
        ];

        foreach ($mapping as $num => $name) {
            DB::table('route_schedules')
                ->where('day_of_week', $num)
                ->update(['day_of_week' => $name]);
        }
    }
};
