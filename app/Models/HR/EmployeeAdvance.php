<?php
namespace App\Models\HR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class EmployeeAdvance extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id', 'advance_number', 'amount', 'request_date', 'status', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'request_date' => 'date']; }

    public function user() { return $this->belongsTo(User::class); }
}
