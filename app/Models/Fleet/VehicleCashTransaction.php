<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;

class VehicleCashTransaction extends Model
{
    protected $fillable = ['vehicle_cash_account_id', 'transaction_date', 'transaction_type', 'amount', 'reference_type', 'reference_id', 'notes'];
    protected $casts = ['transaction_date' => 'date', 'amount' => 'decimal:4'];

    public function cashAccount() { return $this->belongsTo(VehicleCashAccount::class); }
}
