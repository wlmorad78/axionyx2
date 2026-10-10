<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductDiscountRule extends Model
{
    use SoftDeletes;

    /** الحد الأدنى يُفحص على كرتونات كل صنف على حدة. */
    public const SCOPE_PER_ITEM = 'per_item';

    /** الحد الأدنى يُفحص على مجموع كراتين كل أصناف الفاتورة. */
    public const SCOPE_INVOICE_TOTAL = 'invoice_total';

    protected $fillable = [
        'company_id',
        'name',
        'minimum_quantity',
        'discount_per_carton',
        'threshold_scope',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'minimum_quantity' => 'decimal:4',
        'discount_per_carton' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    /** هل الحد الأدنى لهذه القاعدة يُفحص على إجمالي الفاتورة؟ */
    public function usesInvoiceTotalScope(): bool
    {
        return $this->threshold_scope === self::SCOPE_INVOICE_TOTAL;
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'product_discount_rule_items');
    }
}