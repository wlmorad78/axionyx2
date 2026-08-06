<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowEscalation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_step_id',
        'after_hours',
        'escalate_to_role_id',
    ];

    public function workflowStep()
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function escalateToRole()
    {
        return $this->belongsTo(Role::class, 'escalate_to_role_id');
    }
}
