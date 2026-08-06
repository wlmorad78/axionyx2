<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use App\Models\Suppliers\Supplier;

class SupplierTaxProfile extends Model {
    protected $table = 'supplier_tax_profiles';
    protected $fillable = ['supplier_id','tax_registration_no','tax_group_id','tax_exemption_id','is_taxable'];
    protected $casts = ['is_taxable' => 'boolean'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
    public function taxExemption() { return $this->belongsTo(TaxExemption::class); }
}
