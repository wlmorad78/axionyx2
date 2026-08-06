<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model {
    protected $fillable = ['customer_id','transaction_date','reference_type','reference_id','debit','credit','balance'];
    protected $casts = ['transaction_date'=>'date','debit'=>'decimal:2','credit'=>'decimal:2','balance'=>'decimal:2'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
