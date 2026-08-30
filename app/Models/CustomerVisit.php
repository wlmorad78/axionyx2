<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class CustomerVisit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'user_id',
        'employee_id',
        'customer_id',
        'visit_date',
        'check_in_time',
        'check_out_time',
        'latitude',
        'longitude',
        'visit_status',
        'visit_reason',
        'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
