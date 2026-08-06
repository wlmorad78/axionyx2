<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxExemption extends Model {
    use SoftDeletes;
    protected $table = 'tax_exemptions';
    protected $fillable = ['company_id','exemption_code','exemption_name','description','effective_from','effective_to'];
    public function company() { return $this->belongsTo(Company::class); }
}
