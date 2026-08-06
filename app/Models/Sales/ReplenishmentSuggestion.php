<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;

class ReplenishmentSuggestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'replenishment_suggestions';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'suggested_qty',
        'suggestion_date',
        'status',
    ];

    protected $casts = [
        'suggested_qty' => 'decimal:2',
        'suggestion_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
