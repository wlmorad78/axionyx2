<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    use HasFactory;

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
