<?php
namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoiceDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'discount_type', 'discount_value', 'discount_amount', 'reason',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
}
