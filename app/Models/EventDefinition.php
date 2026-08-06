<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDefinition extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'category',
        'source_module',
        'description',
        'payload_schema',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'payload_schema' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(EventLog::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(EventSubscription::class);
    }
}
