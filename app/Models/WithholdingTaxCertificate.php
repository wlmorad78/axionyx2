<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithholdingTaxCertificate extends Model {
    use SoftDeletes;
    protected $table = 'withholding_tax_certificates';
    protected $fillable = ['certificate_no','customer_id','supplier_id','tax_type_id','certificate_date','amount','tax_amount','notes'];
    protected $casts = ['amount' => 'decimal:4','tax_amount' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function taxType() { return $this->belongsTo(TaxType::class); }
}
