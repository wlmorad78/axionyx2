<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_provider_id',
        'endpoint_name',
        'http_method',
        'endpoint_url',
    ];

    public function provider()
    {
        return $this->belongsTo(IntegrationProvider::class, 'integration_provider_id');
    }
}
