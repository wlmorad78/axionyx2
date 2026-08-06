<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRule extends Model {
    use SoftDeletes;
    protected $table = 'tax_rules';
    protected $fillable = ['company_id','rule_name','customer_group_id','item_category_id','tax_group_id','priority','effective_from','effective_to'];
    protected $casts = ['priority' => 'integer'];
    public function company() { return $this->belongsTo(Company::class); }
    public function customerGroup() { return $this->belongsTo(CustomerGroup::class); }
    public function itemCategory() { return $this->belongsTo(ItemCategory::class); }
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
}
