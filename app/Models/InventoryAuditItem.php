<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAuditItem extends Model
{
    protected $fillable = [
        'inventory_audit_id',
        'item_id',
        'unit_id',
        'system_qty',
        'counted_qty',
        'variance_qty',
        'purchase_price',
        'variance_cost',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'counted_qty' => 'decimal:2',
        'variance_qty' => 'decimal:2',
        'purchase_price' => 'decimal:4',
        'variance_cost' => 'decimal:4',
    ];

    public function audit() { return $this->belongsTo(InventoryAudit::class, 'inventory_audit_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
