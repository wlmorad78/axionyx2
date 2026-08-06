<?php
namespace App\Models\Surveys;

use Illuminate\Database\Eloquent\Model;

class CompetitorShelfItem extends Model
{
    protected $fillable = ['shelf_audit_id', 'competitor_product_id', 'facings_count', 'shelf_share_percent'];
    protected $casts = ['facings_count' => 'integer', 'shelf_share_percent' => 'decimal:2'];

    public function shelfAudit() { return $this->belongsTo(ShelfAudit::class); }
    public function competitorProduct() { return $this->belongsTo(CompetitorProduct::class); }
}
