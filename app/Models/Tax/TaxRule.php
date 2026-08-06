<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class TaxRule extends Model {
    use SoftDeletes, BelongsToCompany;
    protected $table = 'tax_rules';
    protected $fillable = ['company_id','rule_name','customer_group_id','item_category_id','tax_group_id','priority','effective_from','effective_to'];
    protected $casts = ['priority' => 'integer'];
    public function company() { return $this->belongsTo(Company::class); }
    public function customerGroup() { return $this->belongsTo(CustomerGroup::class); }
    public function itemCategory() { return $this->belongsTo(ItemCategory::class); }
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
}
