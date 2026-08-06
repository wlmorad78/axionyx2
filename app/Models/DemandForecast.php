<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemandForecast extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'demand_forecasts';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'forecast_month',
        'forecast_qty',
    ];

    protected $casts = [
        'forecast_month' => 'date',
        'forecast_qty' => 'decimal:2',
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
