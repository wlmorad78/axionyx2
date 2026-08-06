<?php

namespace App\Models\Workflows;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class WorkflowTemplate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'template_name',
        'entity_type',
        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function templateSteps()
    {
        return $this->hasMany(WorkflowTemplateStep::class);
    }
}
