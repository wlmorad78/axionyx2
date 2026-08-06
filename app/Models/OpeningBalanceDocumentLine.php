<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

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
