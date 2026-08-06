<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_targets';

    protected $fillable = [
        'kpi_definition_id',
        'employee_id',
        'period_from',
        'period_to',
        'target_value',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'target_value' => 'decimal:2',
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
