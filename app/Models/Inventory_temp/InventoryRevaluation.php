<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class InventoryRevaluation extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','branch_id','warehouse_id','revaluation_no','revaluation_date','reason','notes','status','created_by','approved_by'];
    protected $casts = ['revaluation_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(InventoryRevaluationItem::class); }
    protected static function booted(): void {
        static::creating(function (InventoryRevaluation $model) {
            if (!$model->revaluation_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(revaluation_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^IR-(\d+)$/', $last->revaluation_no, $m)) $next = intval($m[1]) + 1;
                $model->revaluation_no = 'IR-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
