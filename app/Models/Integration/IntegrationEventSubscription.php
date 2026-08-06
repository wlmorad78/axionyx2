<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationEventSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_account_id',
        'integration_event_id',
        'is_enabled',
    ];

    public function account()
    {
        return $this->belongsTo(IntegrationAccount::class, 'integration_account_id');
    }

    public function event()
    {
        return $this->belongsTo(IntegrationEvent::class, 'integration_event_id');
    }
}
