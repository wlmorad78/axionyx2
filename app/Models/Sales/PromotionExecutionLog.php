<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionExecutionLog extends Model
{
    use HasFactory;

    protected $table = 'promotion_execution_logs';

    protected $fillable = [
        'sales_invoice_id',
        'sales_incentive_id',
        'condition_result',
        'reward_result',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function salesIncentive()
    {
        return $this->belongsTo(SalesIncentive::class);
    }
}
