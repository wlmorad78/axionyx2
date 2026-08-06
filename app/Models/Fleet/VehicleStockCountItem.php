<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class VehicleStockCountItem extends Model
{
    protected $fillable = ['vehicle_stock_count_id', 'item_id', 'system_qty', 'actual_qty', 'variance_qty'];
    protected $casts = ['system_qty' => 'decimal:2', 'actual_qty' => 'decimal:2', 'variance_qty' => 'decimal:2'];

    public function stockCount() { return $this->belongsTo(VehicleStockCount::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
