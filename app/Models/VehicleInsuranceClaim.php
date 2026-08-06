<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleInsuranceClaim extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_insurance_claims';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'vehicle_insurance_id',
        'vehicle_accident_id',
        'claim_no',
        'claim_date',
        'claim_amount',
        'approved_amount',
        'status',
        'settlement_date',
        'notes',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'claim_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'settlement_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function insurance()
    {
        return $this->belongsTo(VehicleInsurance::class, 'vehicle_insurance_id');
    }

    public function accident()
    {
        return $this->belongsTo(VehicleAccident::class, 'vehicle_accident_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
