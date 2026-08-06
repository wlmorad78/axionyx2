<?php
namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\Models\User;

class PriceApprovalStep extends Model {
    protected $table = 'price_approval_steps';
    protected $fillable = ['price_approval_request_id','step_no','role_id','user_id','status','action_date','notes'];
    protected $casts = ['step_no' => 'integer','action_date' => 'datetime'];
    public function priceApprovalRequest() { return $this->belongsTo(PriceApprovalRequest::class); }
    public function role() { return $this->belongsTo(Role::class); }
    public function user() { return $this->belongsTo(User::class); }
}
