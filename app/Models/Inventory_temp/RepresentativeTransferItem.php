<?php

namespace App\Models\Inventory_temp;

use Illuminate\Database\Eloquent\Model;

class RepresentativeTransferItem extends Model
{
    protected $fillable = [
        'representative_transfer_id', 'item_id', 'unit_id', 'quantity',
        'base_quantity', 'unit_cost', 'batch_no', 'expiry_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'base_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function transfer() { return $this->belongsTo(RepresentativeTransfer::class, 'representative_transfer_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
