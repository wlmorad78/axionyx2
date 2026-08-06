<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'employee_id',
        'day_of_week',
        'weeks',
        'visit_order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'visit_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
