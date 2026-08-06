<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class TaxGroup extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_groups';
    protected $fillable = ['company_id','group_code','group_name','description'];
    public function company() { return $this->belongsTo(Company::class); }
    public function details() { return $this->hasMany(TaxGroupDetail::class); }
}
