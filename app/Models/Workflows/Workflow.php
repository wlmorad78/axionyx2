<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class Workflow extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'workflow_type_id',
        'workflow_name',
        'priority',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'priority' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function workflowType()
    {
        return $this->belongsTo(WorkflowType::class);
    }

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function conditions()
    {
        return $this->hasMany(WorkflowCondition::class);
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function workflowRoles()
    {
        return $this->hasMany(WorkflowRole::class);
    }

    public function slaRules()
    {
        return $this->hasMany(WorkflowSlaRule::class);
    }
}
