<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\BranchScoped;

class Collection extends Model
{
    use SoftDeletes, BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'collection_no', 'collection_date', 'collection_time',
        'sales_rep_id', 'customer_id', 'payer_customer_id', 'sales_invoice_id', 'payment_method_id',
        'safe_id', 'bank_account_id', 'amount', 'reference_no', 'notes',
        'status', 'created_by', 'approved_by',
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
            if (empty($model->status)) {
                $model->status = 'approved';
            }
            if (empty($model->approved_by)) {
                $model->approved_by = $model->created_by;
            }
        });
    }

    public static function generateNextCode(): string
    {
        $last = static::withoutGlobalScopes()->orderByRaw("CAST(SUBSTR(collection_no, 5) AS INTEGER) DESC")->first();
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
    public function bankAccount() { return $this->belongsTo(\App\Models\BankAccount::class); }
    public function payerCustomer() { return $this->belongsTo(Customer::class, 'payer_customer_id'); }
}
