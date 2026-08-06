<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GpsTrackingSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gps_tracking_sessions';

    protected $fillable = [
        'sales_rep_id',
        'route_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function points()
    {
        return $this->hasMany(GpsTrackingPoint::class);
    }
}
