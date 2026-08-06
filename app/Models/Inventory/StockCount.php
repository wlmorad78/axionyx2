<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class StockCount extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','branch_id','warehouse_id','count_no','count_date','notes','status','created_by','approved_by'];
    protected $casts = ['count_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(StockCountItem::class); }
    protected static function booted(): void {
        static::creating(function (StockCount $model) {
            if (!$model->count_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(count_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^SC-(\d+)$/', $last->count_no, $m)) $next = intval($m[1]) + 1;
                $model->count_no = 'SC-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
