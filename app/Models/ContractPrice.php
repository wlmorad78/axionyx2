<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractPrice extends Model {
    use SoftDeletes;
    protected $table = 'contract_prices';
    protected $fillable = ['customer_agreement_id','item_id','unit_id','contract_price','minimum_qty'];
    protected $casts = ['contract_price' => 'decimal:4','minimum_qty' => 'integer'];
    public function customerAgreement() { return $this->belongsTo(CustomerAgreement::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
