<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class MerchandisingStandardItem extends Model
{
    protected $fillable = ['merchandising_standard_id', 'item_no', 'item_name', 'score', 'display_order'];
    protected $casts = ['item_no' => 'integer', 'score' => 'integer', 'display_order' => 'integer'];

    public function standard() { return $this->belongsTo(MerchandisingStandard::class); }
    public function auditDetails() { return $this->hasMany(MerchandisingAuditDetail::class); }
}
