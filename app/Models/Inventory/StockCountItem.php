<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model {
    protected $fillable = ['stock_count_id','item_id','unit_id','system_qty','counted_qty','variance_qty'];
    protected $casts = ['system_qty'=>'decimal:2','counted_qty'=>'decimal:2','variance_qty'=>'decimal:2'];
    public function stockCount() { return $this->belongsTo(StockCount::class, 'stock_count_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
