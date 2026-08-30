<?php
namespace App\Models\Inventory_temp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Traits\BranchScoped;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\HR\Employee;

class InventoryTransaction extends Model {
    use SoftDeletes, BelongsToCompany, BranchScoped;
    protected $fillable = ['company_id','branch_id','transaction_type_id','warehouse_id','transaction_no','transaction_date','transaction_time','reference_type','reference_id','notes','status','created_by','approved_by'];
    protected $casts = ['transaction_date'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function transactionType() { return $this->belongsTo(InventoryTransactionType::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function createdBy() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedBy() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(InventoryTransactionItem::class); }
    public function reference() { return $this->morphTo(); }

    public static function nextTransactionNo(?int $companyId): string
    {
        $query = static::withTrashed();
        if ($companyId) $query->where('company_id', $companyId);
        $last = $query->orderByRaw("CAST(SUBSTR(transaction_no, 5) AS INTEGER) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^INV-(\d+)$/', $last->transaction_no, $m)) $next = intval($m[1]) + 1;
        return 'INV-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void {
        static::creating(function (InventoryTransaction $model) {
            if (!$model->transaction_no) {
                $model->transaction_no = self::nextTransactionNo($model->company_id);
            }
        });
    }
}
