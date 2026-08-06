<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;

class VehicleWorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_work_orders';

    protected $fillable = [
        'company_id',
        'vehicle_id',
        'work_order_no',
        'maintenance_plan_id',
        'vehicle_maintenance_id',
        'status',
        'priority',
        'assigned_to',
        'start_date',
        'due_date',
        'completed_date',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function maintenancePlan()
    {
        return $this->belongsTo(VehicleMaintenancePlan::class, 'maintenance_plan_id');
    }

    public function maintenance()
    {
        return $this->belongsTo(VehicleMaintenance::class, 'vehicle_maintenance_id');
    }

    public function items()
    {
        return $this->hasMany(VehicleWorkOrderItem::class);
    }
}
