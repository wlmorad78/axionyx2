<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\InvoiceDiscounts;

class SalesInvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'invoice_date', 'item_id', 'unit_id', 'warehouse_id',
        'qty', 'bonus_qty', 'conversion_factor', 'base_quantity',
        'price', 'gross_amount', 'unit_cost', 'total_cost',
        'discount_type', 'discount_value', 'discount_amount',
        'tax_id', 'tax_percent', 'tax_amount', 'net_amount', 'profit', 'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'qty' => 'decimal:2',
        'bonus_qty' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'base_quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'profit' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }

    protected static function booted(): void
    {
        static::saving(function (SalesInvoiceItem $model) {
            $model->syncDiscount();
            $model->syncProfit();
        });
        static::saved(function (SalesInvoiceItem $model) {
            $model->updateParentTotals();
        });
        static::deleted(function (SalesInvoiceItem $model) {
            $model->updateParentTotals();
        });
    }

    /**
     * توحيد خصم السطر قبل الحفظ:
     *  - يشتق gross_amount من (الكمية × السعر) إن لم يُرسل.
     *  - يحسب discount_amount من discount_type/discount_value إن لم يُرسل مبلغ صريح،
     *    ويحدّه بقيمة السطر حتى لا يصبح السطر بالسالب.
     *  - يعيد اشتقاق net_amount متى لم يحدّده المستخدم صراحةً.
     */
    public function syncDiscount(): void
    {
        if ($this->gross_amount === null || $this->gross_amount === '') {
            $this->gross_amount = round((float) $this->qty * (float) $this->price, 2);
        }

        $gross = round((float) $this->gross_amount, 2);
        $netWasProvided = $this->isDirty('net_amount');

        [$type, $value, $amount] = InvoiceDiscounts::line([
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
        ], $gross);

        $this->discount_type = $type;
        $this->discount_value = $value;
        $this->discount_amount = $amount;

        if (!$netWasProvided) {
            $this->net_amount = round(
                $gross - $amount + (float) ($this->tax_amount ?? 0),
                2
            );
        }
    }

    public function syncProfit(): void
    {
        $this->profit = round((float) $this->net_amount - (float) $this->total_cost, 2);
    }

    public function updateParentTotals(): void
    {
        $invoice = $this->salesInvoice;
        if ($invoice) {
            $invoice->recalculateTotals();
        }
    }
}
