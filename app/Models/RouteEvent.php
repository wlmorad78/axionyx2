<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'daily_route_id',
        'customer_id',
        'event_type',
        'description',
        'latitude',
        'longitude',
        'event_time',
        'severity',
        'notes',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function dailyRoute(): BelongsTo
    {
        return $this->belongsTo(DailyRoute::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
