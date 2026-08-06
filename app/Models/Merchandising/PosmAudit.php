<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class PosmAudit extends Model
{
    protected $fillable = ['merchandising_audit_id', 'marketing_material_id', 'is_available', 'condition_status'];
    protected $casts = ['is_available' => 'boolean'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
    public function marketingMaterial() { return $this->belongsTo(MarketingMaterial::class); }
}
