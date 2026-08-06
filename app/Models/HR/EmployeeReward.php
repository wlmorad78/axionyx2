<?php
namespace App\Models\HR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeReward extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['employee_id', 'reward_date', 'amount', 'reason', 'notes'];
    protected function casts(): array { return ['reward_date' => 'date', 'amount' => 'decimal:2']; }

    public function employee() { return $this->belongsTo(Employee::class); }
}
