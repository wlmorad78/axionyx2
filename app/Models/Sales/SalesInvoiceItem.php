<?php
namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;

class SalesInvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id', 'item_id', 'unit_id', 'warehouse_id',
        'qty', 'bonus_qty', 'conversion_factor', 'base_quantity',
        'price', 'gross_amount',
        'discount_type', 'discount_value', 'discount_amount',
        'tax_id', 'tax_percent', 'tax_amount', 'net_amount', 'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'bonus_qty' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'base_quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'gross_amount' => 'decimal:2',
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
        static::saved(function (SalesInvoiceItem $model) {
            $model->updateParentTotals();
        });
        static::deleted(function (SalesInvoiceItem $model) {
            $model->updateParentTotals();
        });
    }

    public function updateParentTotals(): void
    {
        $invoice = $this->salesInvoice;
        if ($invoice) {
            $items = $invoice->items;
            $invoice->update([
                'subtotal' => $items->sum('gross_amount'),
                'item_discount_total' => $items->sum('discount_amount'),
                'tax_total' => $items->sum('tax_amount'),
            ]);
        }
    }
}
