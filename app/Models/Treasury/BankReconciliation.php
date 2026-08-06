<?php
namespace App\Models\Treasury;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model {
    protected $fillable = ['bank_account_id','reconciliation_date','statement_balance','system_balance','difference','notes'];
    protected $casts = ['reconciliation_date'=>'date','statement_balance'=>'decimal:2','system_balance'=>'decimal:2','difference'=>'decimal:2'];
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
}
