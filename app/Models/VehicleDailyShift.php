<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleDailyShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vehicle_id', 'driver_id', 'sales_rep_id', 'shift_date',
        'start_km', 'end_km', 'start_time', 'end_time',
        'notes', 'status',
    ];
    protected $casts = [
        'shift_date' => 'date',
        'start_km' => 'decimal:2',
        'end_km' => 'decimal:2',
    ];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function salesRep() { return $this->belongsTo(Employee::class, 'sales_rep_id'); }

    public function getTotalKmAttribute(): ?float
    {
        if ($this->start_km !== null && $this->end_km !== null) {
            return (float) $this->end_km - (float) $this->start_km;
        }
        return null;
    }
}
