<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class VehicleIdleTime extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_idle_time';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'idle_start',
        'idle_end',
        'duration_minutes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'idle_start' => 'datetime',
        'idle_end' => 'datetime',
        'duration_minutes' => 'decimal:2',
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
