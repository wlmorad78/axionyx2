<?php
namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PurchaseReceiptItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_receipt_id', 'purchase_order_item_id',
        'item_id', 'unit_id', 'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function purchaseReceipt()
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
