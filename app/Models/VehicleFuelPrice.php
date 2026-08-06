<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleFuelPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_fuel_prices';

    protected $fillable = [
        'company_id',
        'fuel_station_id',
        'fuel_type',
        'price_per_liter',
        'effective_date',
    ];

    protected $casts = [
        'price_per_liter' => 'decimal:4',
        'effective_date' => 'date',
    ];

    public function fuelStation()
    {
        return $this->belongsTo(VehicleFuelStation::class, 'fuel_station_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
