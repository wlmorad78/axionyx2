<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use App\Models\Treasury\Treasury;

class VehicleCashAccount extends Model
{
    protected $fillable = ['vehicle_id', 'treasury_id', 'opening_balance', 'current_balance'];
    protected $casts = ['opening_balance' => 'decimal:4', 'current_balance' => 'decimal:4'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function treasury() { return $this->belongsTo(Treasury::class); }
    public function transactions() { return $this->hasMany(VehicleCashTransaction::class); }
}
