<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'organization_unit_type_id',
        'parent_id',
        'code',
        'name_ar',
        'name_en',
        'organizational_level_id',
        'branch_id',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'organization_unit_type_id' => 'integer',
            'parent_id' => 'integer',
            'organizational_level_id' => 'integer',
            'branch_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function unitType()
    {
        return $this->belongsTo(\App\Models\OrganizationUnitType::class, 'organization_unit_type_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function organizationalLevel()
    {
        return $this->belongsTo(\App\Models\OrganizationalLevel::class);
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
