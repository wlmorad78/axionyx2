<?php

namespace App\Support;

class HrStatus
{
    public const LEAVE_PENDING = 'pending';
    public const LEAVE_APPROVED = 'approved';
    public const LEAVE_REJECTED = 'rejected';
    public const LEAVE_CANCELLED = 'cancelled';

    public const PAYROLL_DRAFT = 'draft';
    public const PAYROLL_PROCESSED = 'processed';
    public const PAYROLL_APPROVED = 'approved';
    public const PAYROLL_PAID = 'paid';
    public const PAYROLL_CANCELLED = 'cancelled';

    public const BONUS_PENDING = 'pending';
    public const BONUS_APPROVED = 'approved';
    public const BONUS_PAID = 'paid';
    public const BONUS_CANCELLED = 'cancelled';

    public const ADVANCE_ACTIVE = 'active';
    public const ADVANCE_COMPLETED = 'completed';
    public const ADVANCE_CANCELLED = 'cancelled';

    public const ATTENDANCE_PRESENT = 'present';
    public const ATTENDANCE_ABSENT = 'absent';
    public const ATTENDANCE_LATE = 'late';
    public const ATTENDANCE_HALF_DAY = 'half_day';
    public const ATTENDANCE_ON_LEAVE = 'on_leave';
    public const ATTENDANCE_HOLIDAY = 'holiday';
}
