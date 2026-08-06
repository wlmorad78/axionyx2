<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Geofence extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'geofences';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'center_lat',
        'center_lng',
        'radius_meters',
        'boundary',
        'is_active',
    ];

    protected $casts = [
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'boundary' => 'array',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function events()
    {
        return $this->hasMany(VehicleGeofenceEvent::class);
    }
}
