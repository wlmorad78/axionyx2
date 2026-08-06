<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class VehicleLoadItem extends Model
{
    protected $fillable = ['vehicle_load_id', 'item_id', 'unit_id', 'qty', 'cost'];
    protected $casts = ['qty' => 'decimal:2', 'cost' => 'decimal:4'];

    public function vehicleLoad() { return $this->belongsTo(VehicleLoad::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
