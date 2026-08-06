<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleGeofenceEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_geofence_events';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'geofence_id',
        'event_type',
        'event_time',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function geofence()
    {
        return $this->belongsTo(Geofence::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
