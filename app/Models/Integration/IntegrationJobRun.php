<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationJobRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_job_id',
        'started_at',
        'ended_at',
        'status',
        'records_processed',
        'error_message',
    ];

    public function job()
    {
        return $this->belongsTo(IntegrationJob::class, 'integration_job_id');
    }
}
