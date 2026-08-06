<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MasterDataWorkflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'workflow_name',
        'entity_type',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function steps()
    {
        return $this->hasMany(MasterDataWorkflowStep::class);
    }
}
