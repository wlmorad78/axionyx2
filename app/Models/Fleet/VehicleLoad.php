<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\IssueOrder;
use App\Models\User;

class VehicleLoad extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'vehicle_id', 'load_request_id', 'issue_order_id', 'load_no', 'load_date', 'loaded_value', 'loaded_qty', 'created_by'];
    protected $casts = ['load_date' => 'date', 'loaded_value' => 'decimal:4', 'loaded_qty' => 'decimal:2'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
    public function items() { return $this->hasMany(VehicleLoadItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
