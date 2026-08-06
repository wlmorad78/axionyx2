<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;

class VehicleUnloadItem extends Model
{
    protected $fillable = ['vehicle_unload_id', 'item_id', 'unit_id', 'qty', 'cost'];
    protected $casts = ['qty' => 'decimal:2', 'cost' => 'decimal:4'];

    public function unload() { return $this->belongsTo(VehicleUnload::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
