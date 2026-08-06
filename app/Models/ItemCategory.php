<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

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
