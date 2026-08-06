<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'resource_name',
        'can_create',
        'can_update',
        'can_delete',
        'can_view',
    ];

    public function client()
    {
        return $this->belongsTo(ApiClient::class, 'api_client_id');
    }
}
