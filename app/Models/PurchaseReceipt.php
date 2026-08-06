<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReceipt extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'receipt_no',
        'purchase_order_id', 'supplier_id', 'receipt_date',
        'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdByEmployee()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseReceipt $model) {
            if (! $model->receipt_no) {
                $last = static::orderByRaw("CAST(SUBSTR(receipt_no, 5) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^REC-(\d+)$/', $last->receipt_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->receipt_no = 'REC-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
