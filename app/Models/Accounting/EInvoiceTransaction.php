<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Sales\SalesInvoice;

class EInvoiceTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'e_invoice_transactions';

    protected $fillable = [
        'sales_invoice_id',
        'provider_id',
        'external_reference',
        'status',
        'submitted_at',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function provider()
    {
        return $this->belongsTo(EInvoiceProvider::class, 'provider_id');
    }
}
