<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class MasterDataWorkflowStep extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_data_workflow_id',
        'step_no',
        'role_id',
        'is_required',
    ];

    public function masterDataWorkflow()
    {
        return $this->belongsTo(MasterDataWorkflow::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
