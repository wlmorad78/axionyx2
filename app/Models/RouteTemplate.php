<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RouteTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'route_templates';

    protected $fillable = [
        'route_name',
        'territory_id',
    ];

    protected $casts = [];

    public function territory()
    {
        return $this->belongsTo(SalesTerritory::class, 'territory_id');
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class);
    }
}
