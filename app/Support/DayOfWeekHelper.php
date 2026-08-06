<?php

namespace App\Support;

class DayOfWeekHelper
{
    const DAY_MAP = [
        1 => 'Saturday',
        2 => 'Sunday',
        3 => 'Monday',
        4 => 'Tuesday',
        5 => 'Wednesday',
        6 => 'Thursday',
        7 => 'Friday',
    ];

    const DAY_MAP_AR = [
        1 => 'السبت',
        2 => 'الأحد',
        3 => 'الاثنين',
        4 => 'الثلاثاء',
        5 => 'الأربعاء',
        6 => 'الخميس',
        7 => 'الجمعة',
    ];

    public static function nameToNumber(string $dayName): ?int
    {
        $lower = strtolower($dayName);
        foreach (self::DAY_MAP as $num => $name) {
            if (strtolower($name) === $lower) {
                return $num;
            }
        }
        return null;
    }

    public static function numberToName(int $dayNumber): ?string
    {
        return self::DAY_MAP[$dayNumber] ?? null;
    }

    public static function numberToArabic(int $dayNumber): ?string
    {
        return self::DAY_MAP_AR[$dayNumber] ?? null;
    }

    public static function todayNumber(): int
    {
        $phpDay = (int) date('w');
        return $phpDay === 0 ? 7 : $phpDay;
    }

    public static function todayName(): string
    {
        return self::DAY_MAP[self::todayNumber()];
    }

    public static function parseDays(string $dayOfWeek): array
    {
        return array_map('intval', array_filter(explode(',', $dayOfWeek)));
    }
}
