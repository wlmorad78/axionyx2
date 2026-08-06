<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class TreasuryDailyClosing extends Model
{
    use SoftDeletes;

    protected $fillable = ['treasury_id', 'closing_date', 'opening_balance', 'receipts_total', 'payments_total', 'transfers_in', 'transfers_out', 'expected_balance', 'actual_balance', 'difference_amount', 'status', 'approved_by'];
    protected $casts = ['closing_date' => 'date', 'opening_balance' => 'decimal:4', 'receipts_total' => 'decimal:4', 'payments_total' => 'decimal:4', 'transfers_in' => 'decimal:4', 'transfers_out' => 'decimal:4', 'expected_balance' => 'decimal:4', 'actual_balance' => 'decimal:4', 'difference_amount' => 'decimal:4'];

    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function details() { return $this->hasMany(TreasuryClosingDetail::class); }
}
