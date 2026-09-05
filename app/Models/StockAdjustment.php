<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class StockAdjustment extends Model {
    use SoftDeletes, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','warehouse_id','adjustment_no','adjustment_date','reason','notes','status','created_by','approved_by'];
    protected $casts = ['adjustment_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(StockAdjustmentItem::class); }
    protected static function booted(): void {
        static::creating(function (StockAdjustment $model) {
            if (!$model->adjustment_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(adjustment_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^SA-(\d+)$/', $last->adjustment_no, $m)) $next = intval($m[1]) + 1;
                $model->adjustment_no = 'SA-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
