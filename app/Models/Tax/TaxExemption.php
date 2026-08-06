<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class TaxExemption extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_exemptions';
    protected $fillable = ['company_id','exemption_code','exemption_name','description','effective_from','effective_to'];
    public function company() { return $this->belongsTo(Company::class); }
}
