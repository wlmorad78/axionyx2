<?php
namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class PurchaseReturnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_return_id', 'purchase_invoice_item_id',
        'item_id', 'unit_id', 'qty', 'price', 'tax_amount', 'net_amount',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
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
