<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingCalculationDetail extends Model {
    protected $table = 'pricing_calculation_details';
    protected $fillable = ['pricing_calculation_id','calculation_step','description','amount'];
    protected $casts = ['calculation_step' => 'integer','amount' => 'decimal:4'];
    public function pricingCalculation() { return $this->belongsTo(PricingCalculation::class); }
}
