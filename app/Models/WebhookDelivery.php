<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id', 'event_code', 'payload', 'response_headers',
        'response_body', 'status_code', 'duration_ms', 'status',
        'error_message', 'attempt', 'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_headers' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function webhook() { return $this->belongsTo(Webhook::class); }
}
