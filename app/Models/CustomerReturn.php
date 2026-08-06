<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BranchScoped;

class CustomerReturn extends Model
{
    use SoftDeletes, BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'warehouse_id', 'return_no', 'return_date', 'return_time',
        'sales_invoice_id', 'customer_id', 'sales_rep_id', 'route_id', 'return_reason_id',
        'subtotal', 'tax_total', 'net_total', 'notes', 'status', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'return_time' => 'date:H:i',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'net_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerReturn $model) {
            if (empty($model->return_no)) {
                $model->return_no = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(return_no, 6) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^CRET-(\d+)$/', $last->return_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'CRET-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function route() { return $this->belongsTo(Route::class); }
    public function items() { return $this->hasMany(CustomerReturnItem::class); }
}
