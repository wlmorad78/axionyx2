<?php

namespace App\Models\Inventory_temp;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class ItemSubCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_category_id',
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

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function productCompany()
    {
        return $this->belongsTo(ProductCompany::class);
    }
}
