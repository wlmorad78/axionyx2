<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'department_id',
        'organization_unit_id',
        'position_level_id',
        'job_title_id',
        'job_grade_id',
        'salary_scale_id',
        'reports_to_position_id',
        'parent_id',
        'sort_order',
        'is_active',
        'is_manager',
        'vacancy_count',
        'filled_count',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'department_id' => 'integer',
            'organization_unit_id' => 'integer',
            'position_level_id' => 'integer',
            'job_title_id' => 'integer',
            'job_grade_id' => 'integer',
            'salary_scale_id' => 'integer',
            'reports_to_position_id' => 'integer',
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_manager' => 'boolean',
            'vacancy_count' => 'integer',
            'filled_count' => 'integer',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function organizationUnit()
    {
        return $this->belongsTo(OrganizationUnit::class);
    }

    public function positionLevel()
    {
        return $this->belongsTo(PositionLevel::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function jobGrade()
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function salaryScale()
    {
        return $this->belongsTo(SalaryScale::class);
    }

    public function reportsTo()
    {
        return $this->belongsTo(self::class, 'reports_to_position_id');
    }

    public function directReports()
    {
        return $this->hasMany(self::class, 'reports_to_position_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeAssignment::class, 'position_id');
    }

    public function getAvailableVacanciesAttribute(): int
    {
        return $this->vacancy_count - $this->filled_count;
    }
}
