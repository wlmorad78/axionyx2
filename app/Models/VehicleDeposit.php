<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleDeposit extends Model
{
    use SoftDeletes;

    protected $fillable = ['vehicle_id', 'deposit_no', 'deposit_date', 'amount', 'treasury_id', 'bank_account_id', 'notes'];
    protected $casts = ['deposit_date' => 'date', 'amount' => 'decimal:4'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
}
