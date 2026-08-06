<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'company_id', 'user_id', 'key_hash',
        'key_prefix', 'scopes', 'rate_limit', 'ip_whitelist',
        'is_active', 'usage_count', 'last_used_at', 'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'rate_limit' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'ak_' . Str::random(12);
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function logs() { return $this->hasMany(ApiKeyLog::class); }

    /**
     * Generate a new API key and return the plain text.
     */
    public static function generate(): array
    {
        $plainKey = 'akx_' . Str::random(32);
        $prefix = substr($plainKey, 0, 12);
        $hash = hash('sha256', $plainKey);

        return [
            'key' => $plainKey,
            'hash' => $hash,
            'prefix' => $prefix,
        ];
    }

    /**
     * Validate a plain API key against this record.
     */
    public function validates(string $plainKey): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $plainKey));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasScope(string $scope): bool
    {
        return in_array('*', $this->scopes ?? []) || in_array($scope, $this->scopes ?? []);
    }
}
