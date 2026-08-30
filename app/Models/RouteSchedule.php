<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class RouteSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
