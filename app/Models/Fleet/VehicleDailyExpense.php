<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class VehicleDailyExpense extends Model
{
    use SoftDeletes;

    protected $fillable = ['vehicle_id', 'expense_date', 'expense_type', 'amount', 'notes', 'created_by'];
    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:4'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
