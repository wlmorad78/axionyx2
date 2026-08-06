<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'license_no',
        'license_expiry',
        'mobile',
        'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignments()
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    // ===== New Fleet Management Relationships =====

    public function licenses()
    {
        return $this->hasMany(DriverLicense::class);
    }

    public function training()
    {
        return $this->hasMany(DriverTraining::class);
    }

    public function violations()
    {
        return $this->hasMany(DriverViolation::class);
    }

    public function medicalTests()
    {
        return $this->hasMany(DriverMedicalTest::class);
    }

    public function behaviorScores()
    {
        return $this->hasMany(DriverBehaviorScore::class);
    }
}
