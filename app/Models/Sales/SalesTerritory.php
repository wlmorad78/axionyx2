<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesTerritory extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'sales_territory_type_id',
        'governorate_id',
        'code',
        'name_ar',
        'name_en',
        'warehouse_id',
        'treasury_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'branch_id' => 'integer',
            'sales_territory_type_id' => 'integer',
            'governorate_id' => 'integer',
            'warehouse_id' => 'integer',
            'treasury_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function territoryType()
    {
        return $this->belongsTo(\App\Models\SalesTerritoryType::class, 'sales_territory_type_id');
    }

    public function governorate()
    {
        return $this->belongsTo(\App\Models\Settings\Governorate::class);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Inventory\Warehouse::class);
    }

    public function treasury()
    {
        return $this->belongsTo(\App\Models\Treasury\Treasury::class);
    }
}
