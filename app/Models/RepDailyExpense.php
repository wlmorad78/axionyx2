<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepDailyExpense extends Model
{
    protected $table = 'rep_daily_expenses';

    protected $fillable = [
        'company_id', 'settlement_id', 'expense_type', 'amount', 'notes', 'receipt_image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function settlement() { return $this->belongsTo(RepDailySettlement::class, 'settlement_id'); }
}
