<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model {
    use SoftDeletes, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','request_no','request_date','requested_by','required_date','priority','notes','status','created_by','approved_by'];
    protected $casts = ['request_date'=>'date','required_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function requestedByEmployee() { return $this->belongsTo(Employee::class, 'requested_by'); }
    public function createdByEmployee() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(PurchaseRequestItem::class); }
    protected static function booted(): void {
        static::creating(function (PurchaseRequest $model) {
            if (!$model->request_no) {
                $last = static::orderByRaw("CAST(SUBSTR(request_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^PR-(\d+)$/', $last->request_no, $m)) $next = intval($m[1]) + 1;
                $model->request_no = 'PR-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
