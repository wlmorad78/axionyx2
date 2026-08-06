<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = ['treasury_id', 'adjustment_no', 'adjustment_date', 'adjustment_type', 'amount', 'reason', 'notes'];
    protected $casts = ['adjustment_date' => 'date', 'amount' => 'decimal:4'];

    public function treasury() { return $this->belongsTo(Treasury::class); }
}
