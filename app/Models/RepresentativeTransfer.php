<?php

namespace App\Models;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepresentativeTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'transfer_no', 'client_uuid',
        'from_employee_id', 'to_employee_id', 'status', 'created_by',
        'approved_by', 'approved_at', 'posted_at', 'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function fromEmployee() { return $this->belongsTo(Employee::class, 'from_employee_id'); }
    public function toEmployee() { return $this->belongsTo(Employee::class, 'to_employee_id'); }
    public function items() { return $this->hasMany(RepresentativeTransferItem::class); }
}
