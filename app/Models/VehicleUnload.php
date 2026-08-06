<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleUnload extends Model
{
    use SoftDeletes;

    protected $fillable = ['vehicle_id', 'return_order_id', 'unload_no', 'unload_date', 'notes'];
    protected $casts = ['unload_date' => 'date'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function returnOrder() { return $this->belongsTo(ReturnOrder::class); }
    public function items() { return $this->hasMany(VehicleUnloadItem::class); }
}
