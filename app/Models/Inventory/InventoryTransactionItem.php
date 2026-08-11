<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionItem extends Model {
    protected $fillable = [
        'inventory_transaction_id','item_id','unit_id','conversion_factor','qty','unit_cost','total_cost',
        'batch_no','expiry_date','production_date',
        'from_location_type','from_location_id','to_location_type','to_location_id',
    ];
    protected $casts = [
        'qty'=>'decimal:2','unit_cost'=>'decimal:4','total_cost'=>'decimal:4',
        'expiry_date'=>'date','production_date'=>'date',
    ];

    public function transaction() { return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }

    public function fromLocation() { return $this->morphTo('fromLocation', 'from_location_type', 'from_location_id'); }
    public function toLocation() { return $this->morphTo('toLocation', 'to_location_type', 'to_location_id'); }
}
