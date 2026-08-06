<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCalculationDetail extends Model {
    protected $table = 'tax_calculation_details';
    protected $fillable = ['tax_calculation_id','tax_type_id','tax_rate','taxable_amount','tax_amount','calculation_order'];
    protected $casts = ['tax_rate' => 'decimal:2','taxable_amount' => 'decimal:4','tax_amount' => 'decimal:4','calculation_order' => 'integer'];
    public function taxCalculation() { return $this->belongsTo(TaxCalculation::class); }
    public function taxType() { return $this->belongsTo(TaxType::class); }
}
