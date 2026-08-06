<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Inventory\Item;

class ForecastHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'forecast_history';

    protected $fillable = [
        'item_id',
        'period',
        'actual_qty',
        'forecast_qty',
        'variance',
    ];

    protected $casts = [
        'period' => 'date',
        'actual_qty' => 'decimal:2',
        'forecast_qty' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
