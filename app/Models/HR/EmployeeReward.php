<?php
namespace App\Models\HR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class EmployeeReward extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id', 'reward_date', 'amount', 'reason', 'notes'];
    protected function casts(): array { return ['reward_date' => 'date', 'amount' => 'decimal:2']; }

    public function user() { return $this->belongsTo(User::class); }
}
