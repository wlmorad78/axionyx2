<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $fillable = [
        'event_definition_id',
        'company_id',
        'user_id',
        'payload',
        'status',
        'processed_by',
        'fired_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_by' => 'array',
        'fired_at' => 'datetime',
    ];

    public function eventDefinition()
    {
        return $this->belongsTo(EventDefinition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
