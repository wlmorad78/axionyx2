<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class RefrigeratorAudit extends Model
{
    protected $fillable = ['merchandising_audit_id', 'marketing_asset_id', 'temperature', 'cleanliness_score', 'working_status', 'notes'];
    protected $casts = ['temperature' => 'decimal:2', 'cleanliness_score' => 'decimal:2'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
    public function marketingAsset() { return $this->belongsTo(MarketingAsset::class); }
}
