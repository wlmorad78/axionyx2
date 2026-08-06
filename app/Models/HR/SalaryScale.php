<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class SalaryScale extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'job_grade_id', 'code', 'name_ar', 'name_en', 'minimum_salary', 'maximum_salary', 'is_active', 'notes'];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'job_grade_id' => 'integer',
            'minimum_salary' => 'decimal:2',
            'maximum_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function jobGrade() { return $this->belongsTo(JobGrade::class); }
}
