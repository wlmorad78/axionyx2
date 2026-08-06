<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class VehicleMaintenancePart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_maintenance_parts';

    protected $fillable = [
        'vehicle_maintenance_id',
        'item_id',
        'qty',
        'unit_cost',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function maintenance()
    {
        return $this->belongsTo(VehicleMaintenance::class, 'vehicle_maintenance_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
