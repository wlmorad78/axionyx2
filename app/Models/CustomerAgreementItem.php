<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAgreementItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_agreement_id',
        'item_id',
        'brand_id',
        'item_category_id',
        'discount_type',
        'discount_value',
        'target_qty',
        'target_amount',
        'bonus_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'target_qty' => 'decimal:2',
            'target_amount' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
        ];
    }

    public function customerAgreement()
    {
        return $this->belongsTo(CustomerAgreement::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function brand()
    {
        return $this->belongsTo(ProductCompany::class, 'brand_id');
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }
}
