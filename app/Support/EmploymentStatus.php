<?php

namespace App\Support;

class EmploymentStatus
{
    public const ACTIVE = 'active';

    public const ON_LEAVE = 'on_leave';

    public const TERMINATED = 'terminated';

    public static function labels(): array
    {
        return [
            self::ACTIVE => 'نشط',
            self::ON_LEAVE => 'إجازة',
            self::TERMINATED => 'منتهي',
        ];
    }
}
