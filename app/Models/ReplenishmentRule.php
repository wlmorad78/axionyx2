<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReplenishmentRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'replenishment_rules';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'minimum_qty',
        'maximum_qty',
        'reorder_qty',
        'lead_time_days',
    ];

    protected $casts = [
        'minimum_qty' => 'decimal:2',
        'maximum_qty' => 'decimal:2',
        'reorder_qty' => 'decimal:2',
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
