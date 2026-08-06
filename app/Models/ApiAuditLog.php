<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'action_type',
        'resource_name',
        'resource_id',
        'ip_address',
    ];

    public function client()
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
