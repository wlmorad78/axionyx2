<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransferItem extends Model {
    protected $fillable = ['warehouse_transfer_id','item_id','unit_id','qty'];
    protected $casts = ['qty'=>'decimal:2'];
    public function transfer() { return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
