<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleStockCount extends Model
{
    use SoftDeletes;

    protected $fillable = ['vehicle_id', 'count_no', 'count_date', 'status', 'notes'];
    protected $casts = ['count_date' => 'date'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function items() { return $this->hasMany(VehicleStockCountItem::class); }
}
