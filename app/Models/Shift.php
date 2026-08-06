<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'shift_type_id',
        'code',
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'break_minutes',
        'grace_in_minutes',
        'grace_out_minutes',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'break_minutes' => 'integer',
            'grace_in_minutes' => 'integer',
            'grace_out_minutes' => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function shiftType()
    {
        return $this->belongsTo(ShiftType::class);
    }
}
