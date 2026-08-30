<?php
namespace App\Models\HR;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\Sales\ContractStatus;
use App\Models\Sales\ContractType;
use App\Traits\BelongsToCompany;
use App\Models\User;

class EmployeeContract extends Model {
    use HasFactory, SoftDeletes, BelongsToCompany;
    protected $fillable = ['company_id', 'user_id', 'contract_type_id', 'contract_status_id', 'contract_number', 'start_date', 'end_date', 'basic_salary', 'housing_allowance', 'transportation_allowance', 'other_allowances', 'notes', 'created_by', 'updated_by', 'deleted_by'];
    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'basic_salary' => 'decimal:2', 'housing_allowance' => 'decimal:2', 'transportation_allowance' => 'decimal:2', 'other_allowances' => 'decimal:2']; }

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function contractType() { return $this->belongsTo(ContractType::class); }
    public function contractStatus() { return $this->belongsTo(ContractStatus::class); }
    public function amendments() { return $this->hasMany(EmployeeContractAmendment::class); }
}
