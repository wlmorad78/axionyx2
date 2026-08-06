<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleTire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_tires';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'serial_number',
        'brand',
        'model',
        'size',
        'position',
        'installation_date',
        'installation_km',
        'current_km',
        'status',
        'notes',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'installation_km' => 'decimal:2',
        'current_km' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function movements()
    {
        return $this->hasMany(VehicleTireMovement::class, 'tire_id');
    }

    public function inspections()
    {
        return $this->hasMany(VehicleTireInspection::class, 'tire_id');
    }
}
