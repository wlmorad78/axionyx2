<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_account_id',
        'job_name',
        'schedule_type',
        'next_run_at',
        'is_active',
    ];

    public function account()
    {
        return $this->belongsTo(IntegrationAccount::class, 'integration_account_id');
    }

    public function runs()
    {
        return $this->hasMany(IntegrationJobRun::class);
    }
}
