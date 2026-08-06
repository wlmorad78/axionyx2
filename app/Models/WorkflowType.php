<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class WorkflowType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'workflow_code',
        'workflow_name',
        'entity_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }
}
