<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Models\Treasury\PaymentMethod;
use App\Models\Treasury\Treasury;

class SalesmanDebtPaymentLine extends Model
{
    protected $table = 'salesman_debt_payment_lines';

    protected $fillable = [
        'company_id', 'branch_id', 'salesman_debt_id', 'salesman_account_id',
        'salesman_id', 'payment_date', 'amount', 'remaining_after_payment',
        'payment_method_id', 'treasury_id', 'collection_id',
        'reference_no', 'payment_type', 'notes',
        'received_by', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'remaining_after_payment' => 'decimal:2',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function salesmanDebt(): BelongsTo { return $this->belongsTo(SalesmanDebt::class); }
    public function salesmanAccount(): BelongsTo { return $this->belongsTo(SalesmanAccount::class); }
    public function salesman(): BelongsTo { return $this->belongsTo(Employee::class, 'salesman_id'); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethod::class); }
    public function treasury(): BelongsTo { return $this->belongsTo(Treasury::class); }
    public function collection(): BelongsTo { return $this->belongsTo(Collection::class); }
    public function receivedByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'received_by'); }
    public function createdByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'created_by'); }
}