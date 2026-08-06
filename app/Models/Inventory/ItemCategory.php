<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Models\Company\Company;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'product_company_id',
        'code',
        'name_ar',
        'name_en',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function productCompany()
    {
        return $this->belongsTo(ProductCompany::class);
    }

    public function subCategories()
    {
        return $this->hasMany(ItemSubCategory::class);
    }
}
