<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiDefinition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_definitions';

    protected $fillable = [
        'kpi_code',
        'kpi_name',
        'module',
        'formula',
        'target_type',
    ];

    protected $casts = [];

    public function targets()
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function results()
    {
        return $this->hasMany(KpiResult::class);
    }
}
