<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class TaxReturn extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_returns';
    protected $fillable = ['company_id','tax_period_id','return_no','submission_date','total_sales','total_purchases','output_tax','input_tax','net_tax','status'];
    protected $casts = ['total_sales' => 'decimal:4','total_purchases' => 'decimal:4','output_tax' => 'decimal:4','input_tax' => 'decimal:4','net_tax' => 'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function taxPeriod() { return $this->belongsTo(TaxPeriod::class); }
    public function details() { return $this->hasMany(TaxReturnDetail::class); }
}
