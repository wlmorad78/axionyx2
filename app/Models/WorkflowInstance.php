<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'entity_type',
        'entity_id',
        'instance_no',
        'status',
        'started_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function instanceSteps()
    {
        return $this->hasMany(WorkflowInstanceStep::class);
    }

    public function notifications()
    {
        return $this->hasMany(WorkflowNotification::class);
    }

    public function actionsLog()
    {
        return $this->hasMany(WorkflowActionLog::class);
    }
}
