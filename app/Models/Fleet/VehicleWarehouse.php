<?php
namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\Inventory\Warehouse;

class VehicleWarehouse extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'branch_id', 'vehicle_id', 'warehouse_id', 'code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function stockBalances() { return $this->hasMany(VehicleStockBalance::class); }
    public function inventoryTransactions() { return $this->hasMany(VehicleInventoryTransaction::class); }
}
