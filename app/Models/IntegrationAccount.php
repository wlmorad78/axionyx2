<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_provider_id',
        'account_name',
        'api_key',
        'api_secret',
        'base_url',
        'username',
        'password',
        'token',
        'token_expiry',
        'is_active',
    ];

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }

    public function endpoints()
    {
        return $this->hasMany(IntegrationEndpoint::class);
    }

    public function eventSubscriptions()
    {
        return $this->hasMany(IntegrationEventSubscription::class);
    }

    public function jobs()
    {
        return $this->hasMany(IntegrationJob::class);
    }

    public function errorLogs()
    {
        return $this->hasMany(IntegrationErrorLog::class);
    }
}
