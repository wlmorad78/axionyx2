<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;
use App\Models\Inventory\Item;

class PricingException extends Model {
    protected $table = 'pricing_exceptions';
    protected $fillable = ['pricing_rule_id','customer_id','item_id','exception_price','effective_from','effective_to'];
    protected $casts = ['exception_price' => 'decimal:4'];
    public function pricingRule() { return $this->belongsTo(PricingRule::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
