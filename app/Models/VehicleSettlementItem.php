<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleSettlementItem extends Model
{
    protected $fillable = ['vehicle_settlement_id', 'item_id', 'opening_qty', 'loaded_qty', 'sold_qty', 'returned_qty', 'closing_qty', 'variance_qty'];
    protected $casts = [
        'opening_qty' => 'decimal:2', 'loaded_qty' => 'decimal:2',
        'sold_qty' => 'decimal:2', 'returned_qty' => 'decimal:2',
        'closing_qty' => 'decimal:2', 'variance_qty' => 'decimal:2',
    ];

    public function settlement() { return $this->belongsTo(VehicleSettlement::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
