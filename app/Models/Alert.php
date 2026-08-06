<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_rule_id',
        'entity_type',
        'entity_id',
        'alert_date',
        'severity',
        'status',
        'message',
    ];

    protected $casts = [
        'alert_date' => 'datetime',
    ];

    public function alertRule()
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function actions()
    {
        return $this->hasMany(AlertAction::class);
    }
}
