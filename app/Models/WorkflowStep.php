<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_definition_id',
        'workflow_id',
        'step_no',
        'step_name',
        'role_id',
        'is_required',
        'allow_delegate',
    ];

    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
