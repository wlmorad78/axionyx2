<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleTireMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_tire_movements';

    protected $fillable = [
        'company_id',
        'tire_id',
        'movement_type',
        'from_vehicle_id',
        'to_vehicle_id',
        'from_position',
        'to_position',
        'movement_date',
        'km_at_movement',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'km_at_movement' => 'decimal:2',
    ];

    public function tire()
    {
        return $this->belongsTo(VehicleTire::class, 'tire_id');
    }

    public function fromVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'from_vehicle_id');
    }

    public function toVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'to_vehicle_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
