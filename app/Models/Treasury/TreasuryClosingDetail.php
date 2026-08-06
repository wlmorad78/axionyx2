<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;

class TreasuryClosingDetail extends Model
{
    protected $fillable = ['treasury_daily_closing_id', 'transaction_type', 'amount', 'reference_type', 'reference_id'];
    protected $casts = ['amount' => 'decimal:4'];

    public function closing() { return $this->belongsTo(TreasuryDailyClosing::class); }
}
