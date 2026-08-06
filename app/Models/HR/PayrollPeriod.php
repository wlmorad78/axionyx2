<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class PayrollPeriod extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'period_name',
        'period_start',
        'period_end',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollRuns()
    {
        return $this->hasMany(PayrollRun::class);
    }
}
