<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class TaxPeriod extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_periods';
    protected $fillable = ['company_id','period_name','start_date','end_date','status'];
    public function company() { return $this->belongsTo(Company::class); }
    public function returns() { return $this->hasMany(TaxReturn::class); }
}
