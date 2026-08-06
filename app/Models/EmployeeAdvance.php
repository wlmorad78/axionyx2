<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAdvance extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['employee_id', 'advance_number', 'amount', 'request_date', 'status', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'request_date' => 'date']; }

    public function employee() { return $this->belongsTo(Employee::class); }
}
