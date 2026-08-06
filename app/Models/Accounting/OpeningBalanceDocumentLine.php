<?php
namespace App\Models\Accounting;
use Illuminate\Database\Eloquent\Model;
use App\Models\CRM\Customer;
use App\Models\Inventory\Item;
use App\Models\Suppliers\Supplier;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;

class OpeningBalanceDocumentLine extends Model {
    protected $fillable = ['opening_balance_document_id','account_id','customer_id','supplier_id','item_id','warehouse_id','unit_id','debit','credit','qty','unit_cost','description'];
    protected $casts = ['debit'=>'decimal:2','credit'=>'decimal:2','qty'=>'decimal:2','unit_cost'=>'decimal:4'];
    public function document() { return $this->belongsTo(OpeningBalanceDocument::class, 'opening_balance_document_id'); }
    public function account() { return $this->belongsTo(Account::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
