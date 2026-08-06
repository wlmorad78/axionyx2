<?php

namespace App\Models\Treasury;

use App\Models\BankAccount;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Suppliers\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;

class BankSupplierPayment extends Model
{
    use SoftDeletes, BelongsToCompany, BranchScoped;

    protected $table = 'bank_supplier_payments';

    protected $fillable = [
        'company_id', 'branch_id', 'payment_no', 'bank_account_id',
        'supplier_id', 'purchase_invoice_id', 'payment_date', 'amount',
        'description', 'notes', 'status', 'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    protected static function booted(): void
    {
        static::creating(function (BankSupplierPayment $model) {
            if (!$model->payment_no) {
                $last = static::withTrashed()
                    ->orderByRaw("CAST(SUBSTR(payment_no, 4) AS INTEGER) DESC")
                    ->first();
                $next = 1;
                if ($last && preg_match('/^BP-(\d+)$/', $last->payment_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->payment_no = 'BP-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
