<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;

class InventoryAudit extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'audit_no',
        'audit_date',
        'notes',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'audit_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(InventoryAuditItem::class, 'inventory_audit_id'); }

    protected static function booted(): void
    {
        static::creating(function (InventoryAudit $model) {
            if (!$model->audit_no) {
                $model->audit_no = self::generateNextCode();
            }
        });
    }

    protected static function generateNextCode(): string
    {
        $last = static::withTrashed()
            ->orderByRaw("CAST(SUBSTR(audit_no, 5) AS INTEGER) DESC")
            ->first();
        $next = 1;
        if ($last && preg_match('/^IA-(\d+)$/', $last->audit_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'IA-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
