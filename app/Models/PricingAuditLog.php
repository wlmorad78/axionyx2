<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingAuditLog extends Model {
    protected $table = 'pricing_audit_log';
    protected $fillable = ['reference_type','reference_id','customer_id','item_id','rule_applied','old_price','new_price'];
    protected $casts = ['old_price' => 'decimal:4','new_price' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
