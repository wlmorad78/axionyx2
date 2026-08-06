<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleAccident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_accidents';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'accident_date',
        'location',
        'description',
        'police_report_no',
        'at_fault',
        'other_party_name',
        'other_party_phone',
        'other_party_insurance',
        'repair_cost',
        'insurance_claim_amount',
        'insurance_claim_status',
        'status',
        'images',
        'notes',
    ];

    protected $casts = [
        'accident_date' => 'datetime',
        'at_fault' => 'boolean',
        'repair_cost' => 'decimal:2',
        'insurance_claim_amount' => 'decimal:2',
        'images' => 'array',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
