<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class WorkflowTemplateStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_template_id',
        'step_no',
        'role_id',
        'is_mandatory',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function workflowTemplate()
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
