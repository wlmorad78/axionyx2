<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingRuleCondition extends Model {
    protected $table = 'pricing_rule_conditions';
    protected $fillable = ['pricing_rule_id','condition_type','condition_value'];
    public function pricingRule() { return $this->belongsTo(PricingRule::class); }
}
