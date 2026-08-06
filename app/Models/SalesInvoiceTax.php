<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoiceTax extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'tax_id', 'tax_name', 'tax_percent', 'tax_amount',
    ];

    protected $casts = [
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
}
