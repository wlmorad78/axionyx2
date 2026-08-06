<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;

class Item extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, BranchScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_company_id',
        'item_category_id',
        'item_sub_category_id',
        'code',
        'barcode',
        'name_ar',
        'name_en',
        'short_name',
        'description',
        'is_batch_tracked',
        'is_expiry_tracked',
        'is_serial_tracked',
        'base_unit_id',
        'purchase_unit_id',
        'sales_unit_id',
        'minimum_stock',
        'maximum_stock',
        'reorder_quantity',
        'is_active',
        'notes',
        'image',
        'is_taxable',
    ];

    protected $casts = [
        'is_batch_tracked' => 'boolean',
        'is_expiry_tracked' => 'boolean',
        'is_serial_tracked' => 'boolean',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productCompany()
    {
        return $this->belongsTo(ProductCompany::class);
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function itemSubCategory()
    {
        return $this->belongsTo(ItemSubCategory::class);
    }

    public function baseUnit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function salesUnit()
    {
        return $this->belongsTo(Unit::class, 'sales_unit_id');
    }

    public function itemUnits()
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function prices()
    {
        return $this->hasMany(ItemPrice::class);
    }

    public function barcodes()
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function getStockQtyAttribute()
    {
        return (float) ($this->attributes['stock_qty'] ?? 0);
    }
}
