<?php
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToCompany;
use App\Services\Document;

class ReceiptVoucher extends Document {
    use SoftDeletes, BelongsToCompany, \App\Traits\BranchScoped;

    protected $table = 'receipt_vouchers';

    protected $fillable = [
        'company_id','branch_id','voucher_no','voucher_date','customer_id',
        'safe_id','bank_account_id','amount','notes','status','posted_at',
    ];
    protected $casts = ['voucher_date'=>'date','amount'=>'decimal:2'];

    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function safe() { return $this->belongsTo(Treasury::class, 'safe_id'); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }

    protected function documentType(): string { return 'receipt_voucher'; }
    protected function numberField(): string { return 'voucher_no'; }

    protected function validateBusinessRules(): void
    {
        if ((float)($this->amount ?? 0) <= 0) {
            throw new \DomainException('المبلغ يجب أن يكون أكبر من صفر');
        }
        if (!$this->customer_id) {
            throw new \DomainException('يجب اختيار العميل');
        }
    }

    protected function onApprove(): void {}
    protected function onPost(): void {}
    protected function onCancel(): void {}
    protected function onReopen(): void {}

    protected static function booted(): void {
        static::creating(function (ReceiptVoucher $model) {
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateNumber();
            }
        });
    }
}
