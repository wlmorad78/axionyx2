<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleInventoryTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'vehicle_warehouse_id', 'transaction_no', 'transaction_type', 'transaction_date', 'reference_type', 'reference_id', 'notes', 'created_by'];
    protected $casts = ['transaction_date' => 'date'];

    public function vehicleWarehouse() { return $this->belongsTo(VehicleWarehouse::class); }
    public function items() { return $this->hasMany(VehicleInventoryTransactionItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
