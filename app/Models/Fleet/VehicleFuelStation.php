<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleFuelStation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_fuel_stations';

    protected $fillable = [
        'company_id',
        'name',
        'location',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function prices()
    {
        return $this->hasMany(VehicleFuelPrice::class, 'fuel_station_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
