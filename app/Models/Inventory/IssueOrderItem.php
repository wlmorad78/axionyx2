<?php
namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssueOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'issue_order_id', 'item_id', 'item_unit_id', 'unit_id', 'batch_id',
        'requested_quantity', 'issued_quantity', 'conversion_factor', 'base_quantity',
        'purchase_price', 'sales_price', 'total_amount', 'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'issued_quantity' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'base_quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function issueOrder() { return $this->belongsTo(IssueOrder::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }

    protected static function booted(): void
    {
        static::saved(function (IssueOrderItem $model) {
            $model->updateParentTotals();
        });
        static::deleted(function (IssueOrderItem $model) {
            $model->updateParentTotals();
        });
    }

    public function updateParentTotals(): void
    {
        $order = $this->issueOrder;
        if ($order) {
            $items = $order->items;
            $order->update([
                'total_items_count' => $items->count(),
                'total_quantity' => $items->sum('issued_quantity'),
                'total_amount' => $items->sum('total_amount'),
            ]);
        }
    }
}
