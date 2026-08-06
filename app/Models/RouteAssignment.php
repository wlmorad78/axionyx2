<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'vehicle_id',
        'driver_id',
        'assistant_id',
        'assignment_date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assistant_id');
    }
}
