<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model {
    protected $fillable = ['fiscal_year_id','period_no','period_name','start_date','end_date','is_closed'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','is_closed'=>'boolean'];
    public function fiscalYear() { return $this->belongsTo(FiscalYear::class, 'fiscal_year_id'); }
}
