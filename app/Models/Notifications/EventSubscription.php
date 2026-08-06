<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class EventSubscription extends Model
{
    protected $fillable = [
        'event_definition_id',
        'module_code',
        'handler_class',
        'priority',
        'is_enabled',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'priority' => 'integer',
        'config' => 'array',
    ];

    public function eventDefinition()
    {
        return $this->belongsTo(EventDefinition::class);
    }
}
