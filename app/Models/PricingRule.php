<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PricingRule extends Model {
    use SoftDeletes;
    protected $table = 'pricing_rules';
    protected $fillable = ['company_id','rule_code','rule_name','rule_type','pricing_method_id','priority','start_date','end_date','is_active','status'];
    protected $casts = ['priority' => 'integer','is_active' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function pricingMethod() { return $this->belongsTo(PricingMethod::class); }
    public function conditions() { return $this->hasMany(PricingRuleCondition::class); }
    public function ruleItems() { return $this->hasMany(PricingRuleItem::class); }
    public function exceptions() { return $this->hasMany(PricingException::class); }
}
