<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company\Company;

class VehicleTripHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_trip_history';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'trip_start',
        'trip_end',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'distance_km',
        'duration_minutes',
        'avg_speed_kmh',
        'max_speed_kmh',
        'fuel_consumed_liters',
        'notes',
    ];

    protected $casts = [
        'trip_start' => 'datetime',
        'trip_end' => 'datetime',
        'distance_km' => 'decimal:2',
        'duration_minutes' => 'decimal:2',
        'avg_speed_kmh' => 'decimal:1',
        'max_speed_kmh' => 'decimal:1',
        'fuel_consumed_liters' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
