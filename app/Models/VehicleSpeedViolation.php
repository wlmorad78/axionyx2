<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleSpeedViolation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_speed_violations';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'violation_time',
        'speed_kmh',
        'speed_limit_kmh',
        'latitude',
        'longitude',
        'acknowledged',
        'notes',
    ];

    protected $casts = [
        'violation_time' => 'datetime',
        'speed_kmh' => 'decimal:1',
        'speed_limit_kmh' => 'decimal:1',
        'acknowledged' => 'boolean',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
