<?php
namespace App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PurchaseOrderItem extends Model {
    use SoftDeletes;
    protected $fillable = ['purchase_order_id','item_id','unit_id','qty','received_qty','price','discount_amount','tax_amount','net_amount'];
    protected $casts = ['qty'=>'decimal:2','received_qty'=>'decimal:2','price'=>'decimal:2','discount_amount'=>'decimal:2','tax_amount'=>'decimal:2','net_amount'=>'decimal:2'];
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
