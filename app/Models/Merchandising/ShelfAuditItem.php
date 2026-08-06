<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class ShelfAuditItem extends Model
{
    protected $fillable = ['shelf_audit_id', 'item_id', 'facings_count', 'display_qty', 'shelf_share_percent'];
    protected $casts = ['facings_count' => 'integer', 'display_qty' => 'integer', 'shelf_share_percent' => 'decimal:2'];

    public function shelfAudit() { return $this->belongsTo(ShelfAudit::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
