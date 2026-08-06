<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleInsurance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_insurance';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'insurance_company',
        'policy_number',
        'insurance_type',
        'start_date',
        'end_date',
        'premium_amount',
        'coverage_amount',
        'status',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function claims()
    {
        return $this->hasMany(VehicleInsuranceClaim::class);
    }
}
