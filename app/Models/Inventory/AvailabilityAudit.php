<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class AvailabilityAudit extends Model
{
    protected $fillable = ['merchandising_audit_id', 'item_id', 'is_available', 'stock_qty'];
    protected $casts = ['is_available' => 'boolean', 'stock_qty' => 'integer'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
