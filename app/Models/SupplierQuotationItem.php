<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuotationItem extends Model {
    use SoftDeletes;
    protected $fillable = ['supplier_quotation_id','item_id','unit_id','qty','price','discount_percent','tax_percent','net_price'];
    protected $casts = ['qty'=>'decimal:2','price'=>'decimal:2','discount_percent'=>'decimal:2','tax_percent'=>'decimal:2','net_price'=>'decimal:2'];
    public function supplierQuotation() { return $this->belongsTo(SupplierQuotation::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
