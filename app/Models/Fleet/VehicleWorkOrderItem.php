<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleWorkOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_work_order_items';

    protected $fillable = [
        'vehicle_work_order_id',
        'description',
        'status',
        'labor_cost',
        'parts_cost',
        'notes',
    ];

    protected $casts = [
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
    ];

    public function workOrder()
    {
        return $this->belongsTo(VehicleWorkOrder::class, 'vehicle_work_order_id');
    }
}
