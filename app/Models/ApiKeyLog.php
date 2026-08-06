<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyLog extends Model
{
    protected $fillable = [
        'api_key_id', 'endpoint', 'method', 'status_code',
        'duration_ms', 'ip_address', 'user_agent',
    ];

    public function apiKey() { return $this->belongsTo(ApiKey::class); }
}
