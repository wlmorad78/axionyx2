<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyRouteCustomer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'daily_route_id',
        'customer_id',
        'visit_order',
        'planned_time',
        'actual_check_in',
        'actual_check_out',
        'latitude',
        'longitude',
        'visit_status',
        'notes',
    ];

    protected $casts = [
        'visit_order' => 'integer',
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
