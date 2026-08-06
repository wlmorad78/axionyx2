<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class ApiClient extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_name',
        'client_id',
        'client_secret',
        'allowed_ips',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function permissions()
    {
        return $this->hasMany(ApiPermission::class);
    }

    public function requestLogs()
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    public function rateLimits()
    {
        return $this->hasMany(ApiRateLimit::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ApiAuditLog::class);
    }
}
