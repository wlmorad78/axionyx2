<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DriverBehaviorScore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'driver_behavior_scores';

    protected $fillable = [
        'driver_id',
        'score_date',
        'speeding_events',
        'harsh_braking_events',
        'harsh_acceleration_events',
        'idle_time_minutes',
        'fuel_efficiency_score',
        'overall_score',
        'notes',
    ];

    protected $casts = [
        'score_date' => 'date',
        'idle_time_minutes' => 'decimal:2',
        'fuel_efficiency_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
