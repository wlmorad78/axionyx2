<?php
namespace App\Models\Treasury;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\HR\Employee;
use App\Traits\BranchScoped;

class TreasuryShift extends Model
{
    use SoftDeletes, BranchScoped;

    protected $fillable = ['company_id', 'branch_id', 'treasury_id', 'shift_no', 'cashier_id', 'start_datetime', 'end_datetime', 'opening_balance', 'closing_balance', 'actual_balance', 'difference_amount', 'status'];
    protected $casts = ['start_datetime' => 'datetime', 'end_datetime' => 'datetime', 'opening_balance' => 'decimal:4', 'closing_balance' => 'decimal:4', 'actual_balance' => 'decimal:4', 'difference_amount' => 'decimal:4'];

    public function company() { return $this->belongsTo(Company::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function cashier() { return $this->belongsTo(Employee::class, 'cashier_id'); }
    public function transactions() { return $this->hasMany(TreasuryShiftTransaction::class); }
    public function counts() { return $this->hasMany(TreasuryCount::class); }
}
