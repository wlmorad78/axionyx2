<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTaxProfile extends Model {
    protected $table = 'item_tax_profiles';
    protected $fillable = ['item_id','tax_group_id','tax_exemption_id','is_taxable'];
    protected $casts = ['is_taxable' => 'boolean'];
    public function item() { return $this->belongsTo(Item::class); }
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
    public function taxExemption() { return $this->belongsTo(TaxExemption::class); }
}
