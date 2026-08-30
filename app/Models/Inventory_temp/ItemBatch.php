<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemBatch extends Model {
    use SoftDeletes;
    protected $fillable = ['item_id','batch_no','production_date','expiry_date','purchase_price','qty','remaining_qty'];
    protected $casts = ['production_date'=>'date','expiry_date'=>'date','purchase_price'=>'decimal:4','qty'=>'decimal:2','remaining_qty'=>'decimal:2'];
    public function item() { return $this->belongsTo(Item::class); }
}
