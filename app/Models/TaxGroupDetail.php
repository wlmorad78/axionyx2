<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxGroupDetail extends Model {
    protected $table = 'tax_group_details';
    protected $fillable = ['tax_group_id','tax_type_id','calculation_order'];
    protected $casts = ['calculation_order' => 'integer'];
    public function taxGroup() { return $this->belongsTo(TaxGroup::class); }
    public function taxType() { return $this->belongsTo(TaxType::class); }
}
