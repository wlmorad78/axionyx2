<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReturnItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_return_id', 'sales_invoice_item_id', 'item_id', 'unit_id',
        'qty', 'price', 'gross_amount', 'discount_amount', 'tax_amount', 'net_amount', 'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (CustomerReturnItem $item) {
            $item->recalculateParent();
        });

        static::deleted(function (CustomerReturnItem $item) {
            $item->recalculateParent();
        });
    }

    protected function recalculateParent(): void
    {
        $return = $this->customerReturn;
        if ($return) {
            $return->update([
                'subtotal' => $return->items()->sum('gross_amount'),
                'tax_total' => $return->items()->sum('tax_amount'),
                'net_total' => $return->items()->sum('net_amount'),
            ]);
        }
    }

    public function customerReturn() { return $this->belongsTo(CustomerReturn::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
