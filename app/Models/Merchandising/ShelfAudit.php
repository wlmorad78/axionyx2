<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use App\Models\Surveys\CompetitorShelfItem;

class ShelfAudit extends Model
{
    protected $fillable = ['merchandising_audit_id', 'display_location_id', 'shelf_length', 'shelf_width', 'shelf_height'];
    protected $casts = ['shelf_length' => 'decimal:2', 'shelf_width' => 'decimal:2', 'shelf_height' => 'decimal:2'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
    public function location() { return $this->belongsTo(DisplayLocation::class, 'display_location_id'); }
    public function items() { return $this->hasMany(ShelfAuditItem::class); }
    public function competitorItems() { return $this->hasMany(CompetitorShelfItem::class); }
}
