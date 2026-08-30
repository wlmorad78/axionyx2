<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class WarehouseTransfer extends Model {
    use SoftDeletes, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','from_warehouse_id','to_warehouse_id','transfer_no','transfer_date','notes','status','created_by','approved_by'];
    protected $casts = ['transfer_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function fromWarehouse() { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse() { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(WarehouseTransferItem::class); }
    protected static function booted(): void {
        static::creating(function (WarehouseTransfer $model) {
            if (!$model->transfer_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(transfer_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^WT-(\d+)$/', $last->transfer_no, $m)) $next = intval($m[1]) + 1;
                $model->transfer_no = 'WT-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
