<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleTireInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_tire_inspections';

    protected $fillable = [
        'company_id',
        'tire_id',
        'inspection_date',
        'tread_depth_mm',
        'pressure_psi',
        'condition_notes',
        'inspected_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'tread_depth_mm' => 'decimal:2',
        'pressure_psi' => 'decimal:1',
    ];

    public function tire()
    {
        return $this->belongsTo(VehicleTire::class, 'tire_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
