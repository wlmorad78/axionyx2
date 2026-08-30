<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id', 'leave_type_id', 'from_date', 'to_date', 'days_count', 'reason', 'status', 'approved_by', 'approved_at', 'notes'];
    protected function casts(): array { return ['from_date' => 'date', 'to_date' => 'date', 'days_count' => 'decimal:1', 'approved_at' => 'datetime']; }

    public function user() { return $this->belongsTo(User::class); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
