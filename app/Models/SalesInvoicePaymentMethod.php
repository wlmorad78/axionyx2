<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Models\Company;
use App\Models\PaymentMethod;

class SalesInvoicePaymentMethod extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $table = 'sales_invoice_payment_methods';

    protected $fillable = [
        'company_id',
        'sales_invoice_id',
        'payment_method_id',
        'bank_account_id',
        'amount',
        'method_code',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_account_id' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
