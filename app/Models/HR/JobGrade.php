<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class JobGrade extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = ['company_id', 'code', 'name_ar', 'name_en', 'grade_level', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['company_id' => 'integer', 'grade_level' => 'integer', 'is_active' => 'boolean'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function salaryScales() { return $this->hasMany(SalaryScale::class); }
}
