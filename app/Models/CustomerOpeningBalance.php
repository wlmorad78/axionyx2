<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Employee;

class CustomerOpeningBalance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'customer_id',
        'debit', 'credit', 'balance',
        'balance_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'balance_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
}
