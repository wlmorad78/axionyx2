<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadRequestItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'load_request_id', 'item_id', 'unit_id',
        'quantity', 'conversion_factor', 'base_quantity',
        'unit_price', 'total_price', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'base_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function loadRequest() { return $this->belongsTo(LoadRequest::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function unit() { return $this->belongsTo(Unit::class); }

    protected static function booted(): void
    {
        static::saved(function (LoadRequestItem $model) {
            $model->updateParentTotals();
        });
        static::deleted(function (LoadRequestItem $model) {
            $model->updateParentTotals();
        });
    }

    public function updateParentTotals(): void
    {
        $request = $this->loadRequest;
        if ($request) {
            $items = $request->load('items')->items;
            $request->update([
                'total_items_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_amount' => $items->sum('total_price'),
            ]);
        }
    }
}
