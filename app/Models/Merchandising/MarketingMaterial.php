<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\CRM\CustomerMarketingMaterial;
use App\Models\Inventory\Unit;

class MarketingMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'marketing_materials';

    protected $fillable = [
        'company_id',
        'material_code',
        'material_name',
        'unit_id',
        'cost',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function customerMaterials()
    {
        return $this->hasMany(CustomerMarketingMaterial::class, 'marketing_material_id');
    }
}
