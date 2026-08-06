<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model {
    protected $table = 'tax_rates';
    protected $fillable = ['tax_type_id','rate_percent','effective_from','effective_to','is_default'];
    protected $casts = ['rate_percent' => 'decimal:2','is_default' => 'boolean'];
    public function taxType() { return $this->belongsTo(TaxType::class); }
}
