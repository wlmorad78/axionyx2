<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxPeriod extends Model {
    use SoftDeletes;
    protected $table = 'tax_periods';
    protected $fillable = ['company_id','period_name','start_date','end_date','status'];
    public function company() { return $this->belongsTo(Company::class); }
    public function returns() { return $this->hasMany(TaxReturn::class); }
}
