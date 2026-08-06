<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowActionLog extends Model
{
    use HasFactory;

    protected $table = 'workflow_actions_log';

    protected $fillable = [
        'workflow_instance_id',
        'step_no',
        'action_by',
        'action_type',
        'old_status',
        'new_status',
        'notes',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
