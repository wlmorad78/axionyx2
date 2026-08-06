<?php
namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\Warehouse;

class PurchaseReturn extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'return_no',
        'purchase_invoice_id', 'supplier_id', 'return_date',
        'subtotal', 'tax_total', 'net_total',
        'reason', 'status', 'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'net_total' => 'decimal:2',
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

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
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
        return $this->hasMany(PurchaseReturnItem::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseReturn $model) {
            if (! $model->return_no) {
                $last = static::orderByRaw("CAST(SUBSTR(return_no, 6) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^PRET-(\d+)$/', $last->return_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->return_no = 'PRET-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
