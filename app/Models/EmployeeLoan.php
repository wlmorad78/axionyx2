<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoan extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['employee_id', 'loan_number', 'amount', 'installments_count', 'monthly_installment', 'start_date', 'status', 'notes'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'monthly_installment' => 'decimal:2', 'start_date' => 'date']; }

    public function employee() { return $this->belongsTo(Employee::class); }
}
