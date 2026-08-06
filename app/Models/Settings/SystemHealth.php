<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemHealth extends Model
{
    protected $fillable = ['metric_name', 'metric_value', 'unit', 'metadata', 'recorded_at'];

    protected $casts = [
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get latest snapshot of all metrics.
     */
    public static function latestSnapshot(): array
    {
        $metrics = ['cpu_usage', 'memory_usage', 'disk_usage', 'queue_depth', 'active_users', 'response_time'];
        $result = [];

        foreach ($metrics as $metric) {
            $latest = static::where('metric_name', $metric)
                ->latest('recorded_at')
                ->first();
            $result[$metric] = $latest ? $latest->metric_value : null;
        }

        return $result;
    }

    /**
     * Record a metric.
     */
    public static function record(string $name, float $value, ?string $unit = null, ?array $metadata = null): static
    {
        return static::create([
            'metric_name' => $name,
            'metric_value' => $value,
            'unit' => $unit,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
    }
}
