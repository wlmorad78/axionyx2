<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model {
    protected $fillable = ['stock_adjustment_id','item_id','unit_id','system_qty','actual_qty','difference_qty','unit_cost','difference_value'];
    protected $casts = ['system_qty'=>'decimal:2','actual_qty'=>'decimal:2','difference_qty'=>'decimal:2','unit_cost'=>'decimal:4','difference_value'=>'decimal:4'];
    public function adjustment() { return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
