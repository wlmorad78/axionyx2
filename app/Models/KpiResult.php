<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class KpiResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpi_results';

    protected $fillable = [
        'kpi_definition_id',
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
