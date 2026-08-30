<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'vehicle_code',
        'plate_number',
        'vehicle_type_id',
        'model',
        'year',
        'capacity',
        'status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function assignments()
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function fuelTransactions()
    {
        return $this->hasMany(VehicleFuelTransaction::class);
    }

    public function maintenance()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function dailyExpenses()
    {
        return $this->hasMany(\App\Models\VehicleDailyExpense::class);
    }

    public function loadings()
    {
        return $this->hasMany(VehicleLoading::class);
    }

    // ===== New Fleet Management Relationships =====

    public function documents()
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function ownershipHistory()
    {
        return $this->hasMany(VehicleOwnershipHistory::class);
    }

    public function meterReadings()
    {
        return $this->hasMany(VehicleMeterReading::class);
    }

    public function maintenancePlans()
    {
        return $this->hasMany(VehicleMaintenancePlan::class);
    }

    public function workOrders()
    {
        return $this->hasMany(VehicleWorkOrder::class);
    }

    public function tires()
    {
        return $this->hasMany(VehicleTire::class);
    }

    public function batteries()
    {
        return $this->hasMany(VehicleBattery::class);
    }

    public function fuelCards()
    {
        return $this->hasMany(VehicleFuelCard::class);
    }

    public function accidents()
    {
        return $this->hasMany(VehicleAccident::class);
    }

    public function insurance()
    {
        return $this->hasMany(VehicleInsurance::class);
    }

    public function insuranceClaims()
    {
        return $this->hasMany(VehicleInsuranceClaim::class);
    }

    public function reservations()
    {
        return $this->hasMany(VehicleReservation::class);
    }

    public function geofenceEvents()
    {
        return $this->hasMany(VehicleGeofenceEvent::class);
    }

    public function speedViolations()
    {
        return $this->hasMany(VehicleSpeedViolation::class);
    }

    public function idleTimeRecords()
    {
        return $this->hasMany(VehicleIdleTime::class);
    }

    public function tripHistory()
    {
        return $this->hasMany(VehicleTripHistory::class);
    }

    public function costAnalysis()
    {
        return $this->hasMany(VehicleCostAnalysis::class);
    }

    public function alerts()
    {
        return $this->hasMany(VehicleAlert::class);
    }
}
