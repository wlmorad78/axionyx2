<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;

class CustomerTaxProfile extends Model {
    protected $table = 'customer_tax_profiles';
    protected $fillable = ['customer_id','tax_registration_no','tax_group_id','tax_exemption_id','is_taxable'];
    protected $casts = ['is_taxable' => 'boolean'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
    public function taxExemption() { return $this->belongsTo(TaxExemption::class); }
}
