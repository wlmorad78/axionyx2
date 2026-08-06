<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class Device extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'uuid', 'device_code', 'sales_rep_id', 'company_id',
        'last_sequence', 'device_name', 'device_model', 'os_version',
        'is_active', 'last_sync_at',
    ];

    public function salesRep()
    {
        return $this->belongsTo(Employee::class, 'sales_rep_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getNextSequence(): int
    {
        $this->increment('last_sequence');
        return $this->last_sequence;
    }
}
