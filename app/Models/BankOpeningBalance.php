<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankOpeningBalance extends Model
{
    protected $fillable = ['company_id', 'bank_account_id', 'fiscal_year_id', 'opening_balance', 'notes'];
    protected $casts = ['opening_balance' => 'decimal:2'];

    public function company() { return $this->belongsTo(Company::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function fiscalYear() { return $this->belongsTo(FiscalYear::class); }
}
