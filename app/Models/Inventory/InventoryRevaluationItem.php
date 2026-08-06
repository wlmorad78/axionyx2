<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;

class InventoryRevaluationItem extends Model {
    protected $fillable = ['inventory_revaluation_id','item_id','old_cost','new_cost','difference'];
    protected $casts = ['old_cost'=>'decimal:4','new_cost'=>'decimal:4','difference'=>'decimal:4'];
    public function revaluation() { return $this->belongsTo(InventoryRevaluation::class, 'inventory_revaluation_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
