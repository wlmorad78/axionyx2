<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attendance_record_id',
        'old_check_in',
        'new_check_in',
        'old_check_out',
        'new_check_out',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'old_check_in' => 'datetime',
            'new_check_in' => 'datetime',
            'old_check_out' => 'datetime',
            'new_check_out' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
