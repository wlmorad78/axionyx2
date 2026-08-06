<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeContractAmendment extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['employee_contract_id', 'amendment_number', 'effective_date', 'old_basic_salary', 'new_basic_salary', 'old_end_date', 'new_end_date', 'reason', 'notes', 'created_by'];
    protected function casts(): array { return ['effective_date' => 'date', 'old_basic_salary' => 'decimal:2', 'new_basic_salary' => 'decimal:2', 'old_end_date' => 'date', 'new_end_date' => 'date']; }

    public function contract() { return $this->belongsTo(EmployeeContract::class); }
}
