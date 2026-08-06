<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteStop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'route_stops';

    protected $fillable = [
        'route_template_id',
        'customer_id',
        'sequence_no',
        'expected_duration',
    ];

    protected $casts = [];

    public function routeTemplate()
    {
        return $this->belongsTo(RouteTemplate::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
