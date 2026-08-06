<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class LoginLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_name',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'login_at',
        'logout_at',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Scopes ====================

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('login_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('login_at', today());
    }

    // ==================== Helpers ====================

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function durationInSeconds(): ?int
    {
        if ($this->login_at && $this->logout_at) {
            return $this->login_at->diffInSeconds($this->logout_at);
        }
        return null;
    }

    public static function logSuccess(User $user, array $meta = []): self
    {
        return static::create([
            'user_id' => $user->id,
            'ip_address' => $meta['ip_address'] ?? request()->ip(),
            'user_agent' => $meta['user_agent'] ?? request()->userAgent(),
            'device_name' => $meta['device_name'] ?? null,
            'device_type' => $meta['device_type'] ?? null,
            'browser' => $meta['browser'] ?? null,
            'os' => $meta['os'] ?? null,
            'country' => $meta['country'] ?? null,
            'city' => $meta['city'] ?? null,
            'login_at' => now(),
            'status' => 'success',
        ]);
    }

    public static function logFailure(User $user, string $reason = null, array $meta = []): self
    {
        return static::create([
            'user_id' => $user->id,
            'ip_address' => $meta['ip_address'] ?? request()->ip(),
            'user_agent' => $meta['user_agent'] ?? request()->userAgent(),
            'device_name' => $meta['device_name'] ?? null,
            'device_type' => $meta['device_type'] ?? null,
            'browser' => $meta['browser'] ?? null,
            'os' => $meta['os'] ?? null,
            'country' => $meta['country'] ?? null,
            'city' => $meta['city'] ?? null,
            'login_at' => now(),
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function markLogout(): void
    {
        $this->update(['logout_at' => now()]);
    }
}
