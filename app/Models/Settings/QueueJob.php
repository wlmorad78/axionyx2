<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class QueueJob extends Model
{
    protected $fillable = [
        'queue', 'payload', 'job_name', 'connection', 'status',
        'error_message', 'attempts', 'max_tries', 'duration_ms',
        'started_at', 'finished_at', 'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function scopePending($q) { return $q->where('status', 'pending'); }
    public function scopeRunning($q) { return $q->where('status', 'running'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeFailed($q) { return $q->where('status', 'failed'); }
}
