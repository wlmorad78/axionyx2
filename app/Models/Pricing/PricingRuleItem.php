<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PricingRuleItem extends Model {
    protected $table = 'pricing_rule_items';
    protected $fillable = ['pricing_rule_id','item_id','unit_id','base_price','price','minimum_price'];
    protected $casts = ['base_price' => 'decimal:4','price' => 'decimal:4','minimum_price' => 'decimal:4'];
    public function pricingRule() { return $this->belongsTo(PricingRule::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function quantityBreaks() { return $this->hasMany(QuantityPriceBreak::class); }
}
