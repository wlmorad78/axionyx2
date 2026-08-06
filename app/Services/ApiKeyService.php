<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\ApiKeyLog;
use Illuminate\Support\Facades\RateLimiter;

class ApiKeyService
{
    /**
     * Validate an API key and return it if valid.
     */
    public static function validate(string $plainKey): ?ApiKey
    {
        $prefix = substr($plainKey, 0, 12);

        $key = ApiKey::where('key_prefix', $prefix)
            ->where('is_active', true)
            ->first();

        if (!$key) return null;
        if ($key->isExpired()) return null;
        if (!$key->validates($plainKey)) return null;

        $key->update([
            'usage_count' => $key->usage_count + 1,
            'last_used_at' => now(),
        ]);

        return $key;
    }

    /**
     * Check rate limit for an API key.
     */
    public static function checkRateLimit(ApiKey $key): bool
    {
        $limit = $key->rate_limit ?? ['requests' => 60, 'window' => 60];
        $maxAttempts = $limit['requests'] ?? 60;
        $window = $limit['window'] ?? 60;

        return !RateLimiter::tooManyAttempts(
            'api_key:' . $key->id,
            $maxAttempts
        );
    }

    /**
     * Increment rate limit counter.
     */
    public static function incrementRateLimit(ApiKey $key): void
    {
        $limit = $key->rate_limit ?? ['requests' => 60, 'window' => 60];
        $window = $limit['window'] ?? 60;

        RateLimiter::hit('api_key:' . $key->id, $window);
    }

    /**
     * Check if IP is whitelisted.
     */
    public static function isIpAllowed(ApiKey $key, string $ip): bool
    {
        if (empty($key->ip_whitelist)) return true;
        return in_array($ip, $key->ip_whitelist);
    }

    /**
     * Log an API request.
     */
    public static function logRequest(ApiKey $key, string $endpoint, string $method, int $statusCode, int $durationMs = 0, ?string $ip = null, ?string $userAgent = null): void
    {
        ApiKeyLog::create([
            'api_key_id' => $key->id,
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Create a new API key.
     */
    public static function create(array $data): array
    {
        $generated = ApiKey::generate();

        $key = ApiKey::create([
            'code' => $data['code'] ?? 'ak_' . uniqid(),
            'name' => $data['name'],
            'company_id' => $data['company_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'key_hash' => $generated['hash'],
            'key_prefix' => $generated['prefix'],
            'scopes' => $data['scopes'] ?? ['*'],
            'rate_limit' => $data['rate_limit'] ?? ['requests' => 60, 'window' => 60],
            'ip_whitelist' => $data['ip_whitelist'] ?? null,
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return [
            'key' => $key,
            'plain_key' => $generated['key'],
        ];
    }
}
