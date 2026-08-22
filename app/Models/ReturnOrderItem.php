<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'return_order_id', 'item_id', 'item_unit_id', 'batch_id',
        'returned_quantity', 'sold_quantity', 'loaded_qty', 'sales_price', 'line_total',
        'return_reason_id', 'return_condition', 'notes',
    ];

    protected $casts = [
        'returned_quantity' => 'decimal:2',
        'sold_quantity' => 'decimal:2',
        'loaded_qty' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function returnOrder() { return $this->belongsTo(ReturnOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class, 'item_unit_id'); }

    protected static function booted(): void
    {
        static::saved(function (ReturnOrderItem $model) {
            $model->updateParentTotals();
        });
        static::deleted(function (ReturnOrderItem $model) {
            $model->updateParentTotals();
        });
    }

    public function updateParentTotals(): void
    {
        $order = $this->returnOrder;
        if ($order) {
            $items = $order->items;
            $order->update([
                'total_items_count' => $items->count(),
                'total_quantity' => $items->sum('returned_quantity'),
                'total_amount' => $items->sum('line_total'),
            ]);
        }
    }
}
