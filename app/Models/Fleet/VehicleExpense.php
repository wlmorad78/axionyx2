<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class VehicleExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_expenses';

    protected $fillable = [
        'vehicle_id',
        'expense_date',
        'expense_type',
        'amount',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
