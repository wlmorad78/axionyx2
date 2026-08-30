<?php
namespace App\Models\Treasury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\Settings\Currency;

class BankAccount extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','branch_id','bank_name','branch_name','branch_code','account_number','account_no','account_name','swift_code','iban','currency_id','opening_balance','current_balance','notes','is_active'];
    protected $casts = ['opening_balance'=>'decimal:2','current_balance'=>'decimal:2','is_active'=>'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(\App\Models\Company\Branch::class); }
    public function currency() { return $this->belongsTo(Currency::class); }
}
