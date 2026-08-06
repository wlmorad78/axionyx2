<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model {
    protected $fillable = ['company_id','year_code','start_date','end_date','is_closed'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','is_closed'=>'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function periods() { return $this->hasMany(AccountingPeriod::class, 'fiscal_year_id'); }
}
