<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleBattery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_batteries';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'serial_number',
        'brand',
        'model',
        'voltage',
        'capacity_ah',
        'installation_date',
        'warranty_expiry_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'warranty_expiry_date' => 'date',
        'voltage' => 'decimal:1',
        'capacity_ah' => 'decimal:1',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
