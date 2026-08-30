<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class EmployeeAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'branch_id', 'organization_unit_id', 'position_id',
        'cost_center_id', 'sales_territory_id', 'job_title_id', 'job_grade_id',
        'salary_scale_id', 'direct_manager_id', 'effective_from', 'effective_to',
        'is_current', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'branch_id' => 'integer',
            'organization_unit_id' => 'integer',
            'position_id' => 'integer',
            'cost_center_id' => 'integer',
            'sales_territory_id' => 'integer',
            'job_title_id' => 'integer',
            'job_grade_id' => 'integer',
            'salary_scale_id' => 'integer',
            'direct_manager_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function branch() { return $this->belongsTo(\App\Models\Branch::class); }
    public function organizationUnit() { return $this->belongsTo(\App\Models\OrganizationUnit::class); }
    public function position() { return $this->belongsTo(\App\Models\JobPosition::class, 'position_id'); }
    public function costCenter() { return $this->belongsTo(\App\Models\CostCenter::class); }
    public function salesTerritory() { return $this->belongsTo(\App\Models\SalesTerritory::class); }
    public function jobTitle() { return $this->belongsTo(\App\Models\JobTitle::class); }
    public function jobGrade() { return $this->belongsTo(\App\Models\JobGrade::class); }
    public function salaryScale() { return $this->belongsTo(\App\Models\SalaryScale::class); }
    public function directManager() { return $this->belongsTo(Employee::class, 'direct_manager_id'); }
}
