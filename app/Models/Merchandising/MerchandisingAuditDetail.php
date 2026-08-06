<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class MerchandisingAuditDetail extends Model
{
    protected $fillable = ['merchandising_audit_id', 'merchandising_standard_item_id', 'score', 'remarks'];
    protected $casts = ['score' => 'decimal:2'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
    public function standardItem() { return $this->belongsTo(MerchandisingStandardItem::class); }
}
