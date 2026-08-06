<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GpsTrackingPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gps_tracking_points';

    protected $fillable = [
        'gps_tracking_session_id',
        'latitude',
        'longitude',
        'tracking_time',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'tracking_time' => 'datetime',
    ];

    public function gpsTrackingSession()
    {
        return $this->belongsTo(GpsTrackingSession::class);
    }
}
