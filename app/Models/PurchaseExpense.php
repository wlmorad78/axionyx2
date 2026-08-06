<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseExpense extends Model
{
    use SoftDeletes, \App\Traits\BranchScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'expense_no',
        'purchase_invoice_id', 'expense_type', 'amount', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PurchaseExpense $model) {
            if (! $model->expense_no) {
                $last = static::orderByRaw("CAST(SUBSTR(expense_no, 4) AS INTEGER) DESC")->first();
                $next = 1;
                if ($last && preg_match('/^PE-(\d+)$/', $last->expense_no, $m)) {
                    $next = intval($m[1]) + 1;
                }
                $model->expense_no = 'PE-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
