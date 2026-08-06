<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PricingCalculation extends Model {
    protected $table = 'pricing_calculations';
    protected $fillable = ['reference_type','reference_id','customer_id','item_id','unit_id','base_price','final_price','discount_amount','discount_percent','pricing_rule_id'];
    protected $casts = ['base_price' => 'decimal:4','final_price' => 'decimal:4','discount_amount' => 'decimal:4','discount_percent' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function pricingRule() { return $this->belongsTo(PricingRule::class); }
    public function details() { return $this->hasMany(PricingCalculationDetail::class); }
}
