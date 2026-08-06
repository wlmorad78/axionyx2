<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;

class TaxCalculation extends Model {
    protected $table = 'tax_calculations';
    protected $fillable = ['reference_type','reference_id','calculation_date','taxable_amount','tax_amount','total_amount'];
    protected $casts = ['taxable_amount' => 'decimal:4','tax_amount' => 'decimal:4','total_amount' => 'decimal:4'];
    public function details() { return $this->hasMany(TaxCalculationDetail::class); }
}
