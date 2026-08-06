<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryCount extends Model
{
    use SoftDeletes;

    protected $fillable = ['treasury_shift_id', 'count_no', 'count_date', 'counted_by', 'expected_amount', 'actual_amount', 'difference_amount', 'notes'];
    protected $casts = ['count_date' => 'date', 'expected_amount' => 'decimal:4', 'actual_amount' => 'decimal:4', 'difference_amount' => 'decimal:4'];

    public function shift() { return $this->belongsTo(TreasuryShift::class); }
    public function countedByEmployee() { return $this->belongsTo(Employee::class, 'counted_by'); }
    public function details() { return $this->hasMany(TreasuryCountDetail::class); }
}
