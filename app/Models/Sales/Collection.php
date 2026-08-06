<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use App\Traits\BranchScoped;
use App\Models\Treasury\PaymentMethod;

class Collection extends Model
{
    use SoftDeletes, BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'collection_no', 'collection_date', 'collection_time',
        'sales_rep_id', 'customer_id', 'sales_invoice_id', 'payment_method_id',
        'safe_id', 'bank_account_id', 'amount', 'reference_no', 'notes',
        'status', 'created_by', 'approved_by',
        'collection_type', 'debt_id', 'debt_payment_line_id', 'collected_from_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'collection_time' => 'date:H:i',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Collection $model) {
            if (empty($model->collection_no)) {
                $model->collection_no = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(collection_no, 5) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^COL-(\d+)$/', $last->collection_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'COL-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function salesmanDebt() { return $this->belongsTo(SalesmanDebt::class, 'debt_id'); }
    public function debtPaymentLine() { return $this->belongsTo(SalesmanDebtPaymentLine::class, 'debt_payment_line_id'); }
    public function collectedFrom() { return $this->belongsTo(Employee::class, 'collected_from_id'); }

    public static function generateCollectionNoForDebt(SalesmanDebt $debt): string
    {
        $last = static::orderByRaw("CAST(SUBSTR(collection_no, 5) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^COL-(\d+)$/', $last->collection_no, $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'COL-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
