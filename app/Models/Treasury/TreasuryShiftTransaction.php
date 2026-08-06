<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryShiftTransaction extends Model
{
    protected $fillable = ['treasury_shift_id', 'transaction_type', 'reference_type', 'reference_id', 'amount', 'transaction_datetime', 'notes'];
    protected $casts = ['amount' => 'decimal:4', 'transaction_datetime' => 'datetime'];

    public function shift() { return $this->belongsTo(TreasuryShift::class); }
}
