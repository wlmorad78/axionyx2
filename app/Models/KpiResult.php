<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_results';

    protected $fillable = [
        'kpi_definition_id',
        'employee_id',
        'actual_value',
        'achievement_percent',
        'calculated_at',
    ];

    protected $casts = [
        'actual_value' => 'decimal:2',
        'achievement_percent' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function kpiDefinition()
    {
        return $this->belongsTo(KpiDefinition::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
