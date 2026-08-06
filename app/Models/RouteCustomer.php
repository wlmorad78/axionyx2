<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteCustomer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'customer_id',
        'visit_order',
        'visit_frequency',
        'weeks',
        'is_mandatory',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'visit_order' => 'integer',
        'is_mandatory' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
