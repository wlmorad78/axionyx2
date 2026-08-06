<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class VehicleInventoryTransactionItem extends Model
{
    protected $fillable = ['vehicle_inventory_transaction_id', 'item_id', 'unit_id', 'qty', 'unit_cost', 'total_cost'];
    protected $casts = ['qty' => 'decimal:2', 'unit_cost' => 'decimal:4', 'total_cost' => 'decimal:4'];

    public function transaction() { return $this->belongsTo(VehicleInventoryTransaction::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
