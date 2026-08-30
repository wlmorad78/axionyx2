<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeePenalty extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id', 'penalty_date', 'amount', 'reason', 'notes'];
    protected function casts(): array { return ['penalty_date' => 'date', 'amount' => 'decimal:2']; }

    public function user() { return $this->belongsTo(User::class); }
}
