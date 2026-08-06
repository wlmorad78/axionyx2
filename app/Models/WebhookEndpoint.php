<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'webhook_name',
        'target_url',
        'secret_key',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}
