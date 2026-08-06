<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoiceIncentive extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'sales_incentive_id', 'incentive_name', 'incentive_type', 'benefit_amount', 'notes',
    ];

    protected $casts = [
        'benefit_amount' => 'decimal:2',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function salesIncentive() { return $this->belongsTo(SalesIncentive::class); }
}
