<?php
namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;

class MerchandisingAuditPhoto extends Model
{
    protected $fillable = ['merchandising_audit_id', 'photo_type', 'file_path', 'taken_at'];
    protected $casts = ['taken_at' => 'datetime'];

    public function audit() { return $this->belongsTo(MerchandisingAudit::class); }
}
