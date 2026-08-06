<?php
namespace App\Models\Tax;

use Illuminate\Database\Eloquent\Model;

class TaxReturnDetail extends Model {
    protected $table = 'tax_return_details';
    protected $fillable = ['tax_return_id','tax_type_id','taxable_amount','tax_amount'];
    protected $casts = ['taxable_amount' => 'decimal:4','tax_amount' => 'decimal:4'];
    public function taxReturn() { return $this->belongsTo(TaxReturn::class); }
    public function taxType() { return $this->belongsTo(TaxType::class); }
}
