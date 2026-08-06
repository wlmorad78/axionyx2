<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class Budget extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'budgets';

    protected $fillable = [
        'company_id',
        'budget_code',
        'budget_name',
        'fiscal_year_id',
    ];

    protected $casts = [];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function lines()
    {
        return $this->hasMany(BudgetLine::class);
    }
}
