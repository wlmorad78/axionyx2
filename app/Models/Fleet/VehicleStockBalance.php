<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class VehicleStockBalance extends Model
{
    protected $fillable = ['vehicle_warehouse_id', 'item_id', 'qty', 'average_cost', 'stock_value'];
    protected $casts = ['qty' => 'decimal:2', 'average_cost' => 'decimal:4', 'stock_value' => 'decimal:4'];

    public function vehicleWarehouse() { return $this->belongsTo(VehicleWarehouse::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
