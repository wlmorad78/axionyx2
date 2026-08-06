<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model {
    use SoftDeletes, \App\Traits\BranchScoped;
    protected $fillable = ['company_id','branch_id','po_no','supplier_id','quotation_id','order_date','expected_delivery_date','subtotal','discount_total','tax_total','net_total','notes','status','created_by','approved_by'];
    protected $casts = ['order_date'=>'date','expected_delivery_date'=>'date','subtotal'=>'decimal:2','discount_total'=>'decimal:2','tax_total'=>'decimal:2','net_total'=>'decimal:2'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function quotation() { return $this->belongsTo(SupplierQuotation::class, 'quotation_id'); }
    public function createdByEmployee() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    protected static function booted(): void {
        static::creating(function (PurchaseOrder $model) {
            if (!$model->po_no) {
                $last = static::orderByRaw("CAST(SUBSTR(po_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^PO-(\d+)$/', $last->po_no, $m)) $next = intval($m[1]) + 1;
                $model->po_no = 'PO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
