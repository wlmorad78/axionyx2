<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;

class SalesmanAccountMovement extends Model
{
    protected $table = 'salesman_account_movements';

    protected $fillable = [
        'company_id', 'branch_id', 'salesman_account_id', 'salesman_id',
        'movement_date', 'movement_type', 'reference_type', 'reference_id',
        'document_no', 'debit', 'credit', 'balance', 'description', 'notes',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function salesmanAccount(): BelongsTo { return $this->belongsTo(SalesmanAccount::class); }
    public function salesman(): BelongsTo { return $this->belongsTo(Employee::class, 'salesman_id'); }
    public function createdByEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'created_by'); }
}