<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleFuelTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'transaction_date',
        'transaction_time',
        'odometer',
        'fuel_qty',
        'fuel_cost',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'transaction_time' => 'datetime:H:i',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
