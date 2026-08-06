<?php
namespace App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PurchaseRequestItem extends Model {
    use SoftDeletes;
    protected $fillable = ['purchase_request_id','item_id','unit_id','qty','notes'];
    protected $casts = ['qty'=>'decimal:2'];
    public function purchaseRequest() { return $this->belongsTo(PurchaseRequest::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
