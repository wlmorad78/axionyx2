<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;

class CustomerPriceList extends Model {
    protected $table = 'customer_price_lists';
    protected $fillable = ['customer_id','price_list_id','priority','effective_from','effective_to'];
    protected $casts = ['priority' => 'integer'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function priceList() { return $this->belongsTo(PriceList::class); }
}
