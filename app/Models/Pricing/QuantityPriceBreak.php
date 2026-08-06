<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

class QuantityPriceBreak extends Model {
    protected $table = 'quantity_price_breaks';
    protected $fillable = ['pricing_rule_item_id','from_qty','to_qty','price','discount_percent'];
    protected $casts = ['from_qty' => 'integer','to_qty' => 'integer','price' => 'decimal:4','discount_percent' => 'decimal:2'];
    public function pricingRuleItem() { return $this->belongsTo(PricingRuleItem::class); }
}
