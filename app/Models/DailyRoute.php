<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'employee_id',
        'route_date',
        'status',
        'planned_start_time',
        'planned_end_time',
        'actual_start_time',
        'actual_end_time',
        'planned_customers',
        'visited_customers',
        'total_distance_km',
        'notes',
    ];

    protected $casts = [
        'route_date' => 'date',
        'planned_customers' => 'integer',
        'visited_customers' => 'integer',
        'total_distance_km' => 'decimal:2',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(DailyRouteCustomer::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RouteEvent::class);
    }
}
