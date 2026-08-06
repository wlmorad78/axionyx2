<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'salary_component_type_id',
        'code',
        'name_ar',
        'name_en',
        'is_taxable',
        'affects_insurance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'affects_insurance' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salaryComponentType()
    {
        return $this->belongsTo(SalaryComponentType::class);
    }
}
