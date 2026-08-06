<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class WorkflowRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'role_id',
        'can_approve',
        'can_reject',
        'can_return',
    ];

    protected $casts = [
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_return' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
