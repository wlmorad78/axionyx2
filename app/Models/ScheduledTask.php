<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledTask extends Model
{
    protected $fillable = [
        'command', 'schedule', 'status', 'output', 'error_message',
        'exit_code', 'duration_ms', 'started_at', 'finished_at', 'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
