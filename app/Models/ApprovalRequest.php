<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workflow_definition_id',
        'reference_type',
        'reference_id',
        'requested_by',
        'request_date',
        'current_step',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actions()
    {
        return $this->hasMany(ApprovalAction::class);
    }
}
