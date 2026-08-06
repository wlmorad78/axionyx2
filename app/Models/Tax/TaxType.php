<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Models\Company\Company;

class TaxType extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_types';
    protected $fillable = ['company_id','tax_code','tax_name','tax_category','description','is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function rates() { return $this->hasMany(TaxRate::class); }
    public function groupDetails() { return $this->hasMany(TaxGroupDetail::class); }
}
