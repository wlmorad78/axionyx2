<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\InvoiceDiscounts;

class SalesInvoiceDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'discount_type', 'discount_value', 'discount_amount', 'reason',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (SalesInvoiceDiscount $model) {
            $model->syncAmount();
        });
        static::saved(function (SalesInvoiceDiscount $model) {
            $model->refreshParentTotals();
        });
        static::deleted(function (SalesInvoiceDiscount $model) {
            $model->refreshParentTotals();
        });
        static::restored(function (SalesInvoiceDiscount $model) {
            $model->refreshParentTotals();
        });
    }

    /**
     * اشتقاق مبلغ الخصم من (discount_type + discount_value) إن لم يُرسل مبلغ صريح،
     * مع منع تجاوز مجموع خصومات الفاتورة للأساس المتاح.
     */
    public function syncAmount(): void
    {
        $invoice = $this->salesInvoice;
        if (!$invoice) {
            return;
        }

        $explicit = (float) ($this->discount_amount ?? 0);
        $remaining = round($invoice->discountBase(), 2);

        $others = $invoice->discounts()
            ->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))
            ->sum('discount_amount');

        $remaining = round(max(0, $remaining - (float) $others), 2);

        if ($explicit > 0) {
            $this->discount_amount = round(min($explicit, $remaining), 2);
            return;
        }

        $this->discount_amount = InvoiceDiscounts::amount(
            $remaining,
            $this->discount_type,
            $this->discount_value
        );
    }

    /**
     * أي تعديل على صفوف خصم الفاتورة يعيد حساب (invoice_discount_total)
     * و (net_total) و (remaining_amount) على الفاتورة الأم فوراً.
     */
    public function refreshParentTotals(): void
    {
        $invoice = $this->salesInvoice;
        if ($invoice) {
            $invoice->recalculateTotals();
        }
    }

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
}
