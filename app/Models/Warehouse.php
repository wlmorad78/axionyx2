<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class Warehouse extends Model
{
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_type_id',
        'code',
        'name',
        'name_ar',
        'name_en',
        'phone',
        'type',
        'address',
        'manager_employee_id',
        'notes',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouseType()
    {
        return $this->belongsTo(WarehouseType::class);
    }

    public function manager()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'manager_employee_id');
    }
}
