<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleMaintenancePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_maintenance_plans';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'plan_name',
        'maintenance_type',
        'trigger_type',
        'trigger_value',
        'description',
        'estimated_cost',
        'is_active',
    ];

    protected $casts = [
        'trigger_value' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'is_active' => 'boolean',
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
