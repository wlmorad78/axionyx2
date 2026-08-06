<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CRM\Customer;
use App\Models\Inventory\Item;
use App\Models\User;

class PriceApprovalRequest extends Model {
    use SoftDeletes;
    protected $table = 'price_approval_requests';
    protected $fillable = ['request_no','customer_id','item_id','requested_price','current_price','requested_by','status'];
    protected $casts = ['requested_price' => 'decimal:4','current_price' => 'decimal:4'];
    public function customer() { return $this->belongsTo(Customer::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function steps() { return $this->hasMany(PriceApprovalStep::class); }
}
