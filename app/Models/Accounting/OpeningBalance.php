<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class OpeningBalance extends Model {
    use BelongsToCompany;
    protected $fillable = ['company_id','fiscal_year_id','account_id','cost_center_id','opening_debit','opening_credit'];
    protected $casts = ['opening_debit'=>'decimal:2','opening_credit'=>'decimal:2'];
    public function company() { return $this->belongsTo(Company::class); }
    public function fiscalYear() { return $this->belongsTo(FiscalYear::class, 'fiscal_year_id'); }
    public function account() { return $this->belongsTo(Account::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
}
