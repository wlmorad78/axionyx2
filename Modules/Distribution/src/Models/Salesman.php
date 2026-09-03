<?php

namespace App\Modules\Distribution\src\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;

class Salesman extends Model
{
    use HasUuids, SoftDeletes, BelongsToCompany, BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'sales_team_id',
        'supervisor_id',
        'code',
        'name',
        'name_en',
        'phone',
        'mobile',
        'email',
        'national_id',
        'hire_date',
        'target_amount',
        'commission_type',
        'commission_value',
        'commission_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'hire_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ─── Relationships ─────────────────────────────────────────

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    public function salesTeam()
    {
        return $this->belongsTo(SalesTeam::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Salesman::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Salesman::class, 'supervisor_id');
    }

    public function assignments()
    {
        return $this->hasMany(SalesmanAssignment::class);
    }

    public function accounts()
    {
        return $this->hasMany(SalesmanAccount::class);
    }

    public function debts()
    {
        return $this->hasMany(SalesmanDebt::class);
    }

    public function settlements()
    {
        return $this->hasMany(SalesmanSettlement::class);
    }

    public function customers()
    {
        return $this->hasMany(\App\Models\Customer::class, 'default_salesman_id');
    }

    public function salesInvoices()
    {
        return $this->hasMany(\App\Models\SalesInvoice::class);
    }
}
