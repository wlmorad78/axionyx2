<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class InventoryOpeningBalance extends Model {
    protected $fillable = ['company_id','branch_id','warehouse_id','item_id','unit_id','opening_date','qty','unit_cost','total_cost','created_by'];
    protected $casts = ['opening_date'=>'date','qty'=>'decimal:2','unit_cost'=>'decimal:4','total_cost'=>'decimal:4'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
}
