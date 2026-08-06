<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Company\Company;

class VehicleCostAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_cost_analysis';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'analysis_period',
        'fuel_cost',
        'maintenance_cost',
        'insurance_cost',
        'tire_cost',
        'battery_cost',
        'depreciation_cost',
        'salary_cost',
        'violation_fines',
        'other_cost',
        'total_cost',
        'total_km',
        'cost_per_km',
    ];

    protected $casts = [
        'fuel_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'insurance_cost' => 'decimal:2',
        'tire_cost' => 'decimal:2',
        'battery_cost' => 'decimal:2',
        'depreciation_cost' => 'decimal:2',
        'salary_cost' => 'decimal:2',
        'violation_fines' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_km' => 'decimal:2',
        'cost_per_km' => 'decimal:4',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
