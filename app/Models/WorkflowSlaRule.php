<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowSlaRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_no',
        'target_hours',
        'warning_hours',
    ];

    protected $casts = [
        'target_hours' => 'integer',
        'warning_hours' => 'integer',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
