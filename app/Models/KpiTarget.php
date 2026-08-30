<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class KpiTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_targets';

    protected $fillable = [
        'kpi_definition_id',
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
