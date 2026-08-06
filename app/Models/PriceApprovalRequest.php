<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
