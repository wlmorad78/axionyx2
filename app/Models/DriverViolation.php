<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DriverViolation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'driver_violations';

    protected $fillable = [
        'driver_id',
        'violation_date',
        'violation_type',
        'description',
        'fine_amount',
        'points',
        'status',
        'notes',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'fine_amount' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
