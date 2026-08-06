<?php
namespace App\Models\Treasury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;

class BankTransfer extends Model {
    use SoftDeletes;
    protected $fillable = ['company_id','from_bank_account_id','to_bank_account_id','transfer_no','transfer_date','amount','notes'];
    protected $casts = ['transfer_date'=>'date','amount'=>'decimal:2'];
    public function company() { return $this->belongsTo(Company::class); }
    public function fromBankAccount() { return $this->belongsTo(BankAccount::class, 'from_bank_account_id'); }
    public function toBankAccount() { return $this->belongsTo(BankAccount::class, 'to_bank_account_id'); }
    protected static function booted(): void {
        static::creating(function (BankTransfer $model) {
            if (!$model->transfer_no) {
                $last = static::withTrashed()->orderByRaw("CAST(SUBSTR(transfer_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^BT-(\d+)$/', $last->transfer_no, $m)) $next = intval($m[1]) + 1;
                $model->transfer_no = 'BT-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
