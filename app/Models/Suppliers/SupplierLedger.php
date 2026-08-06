<?php
namespace App\Models\Suppliers;
use Illuminate\Database\Eloquent\Model;

class SupplierLedger extends Model {
    protected $table = 'supplier_ledger';
    protected $fillable = ['supplier_id','transaction_date','reference_type','reference_id','debit','credit','balance'];
    protected $casts = ['transaction_date'=>'date','debit'=>'decimal:2','credit'=>'decimal:2','balance'=>'decimal:2'];
    public function supplier() { return $this->belongsTo(Supplier::class); }
}
