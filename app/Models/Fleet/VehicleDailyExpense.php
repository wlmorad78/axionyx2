<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class VehicleDailyExpense extends Model
{
    use SoftDeletes;

    protected $fillable = ['vehicle_id', 'employee_id', 'uuid', 'expense_date', 'expense_time', 'expense_type', 'amount', 'km', 'quantity', 'notes', 'created_by'];
    protected $casts = ['expense_date' => 'date', 'expense_time' => 'datetime:H:i', 'amount' => 'decimal:4', 'km' => 'decimal:2', 'quantity' => 'decimal:2'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
