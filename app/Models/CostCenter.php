<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'cost_center_type_id',
        'parent_id',
        'branch_id',
        'code',
        'name_ar',
        'name_en',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'cost_center_type_id' => 'integer',
            'parent_id' => 'integer',
            'branch_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function costCenterType()
    {
        return $this->belongsTo(\App\Models\CostCenterType::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
