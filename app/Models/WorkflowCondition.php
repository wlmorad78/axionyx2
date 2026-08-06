<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'field_name',
        'operator',
        'field_value',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
