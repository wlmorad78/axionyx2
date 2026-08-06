<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuotation extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','branch_id','quotation_no','supplier_id','quotation_date','valid_until','notes','status','created_by'];
    protected $casts = ['quotation_date'=>'date','valid_until'=>'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function createdByEmployee() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function items() { return $this->hasMany(SupplierQuotationItem::class); }
    protected static function booted(): void {
        static::creating(function (SupplierQuotation $model) {
            if (!$model->quotation_no) {
                $last = static::orderByRaw("CAST(SUBSTR(quotation_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^SQ-(\d+)$/', $last->quotation_no, $m)) $next = intval($m[1]) + 1;
                $model->quotation_no = 'SQ-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
