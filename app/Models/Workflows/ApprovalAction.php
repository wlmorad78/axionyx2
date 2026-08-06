<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApprovalAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'approval_request_id',
        'workflow_step_id',
        'user_id',
        'action',
        'notes',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function workflowStep()
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
