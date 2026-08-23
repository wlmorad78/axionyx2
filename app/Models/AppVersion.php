<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'version',
        'build',
        'platform',
        'download_url',
        'force_update',
        'release_notes',
        'release_date',
        'minimum_supported_version',
        'minimum_supported_build',
        'file_size',
        'checksum',
        'is_active',
    ];

    protected $casts = [
        'build' => 'integer',
        'force_update' => 'boolean',
        'release_notes' => 'array',
        'release_date' => 'date',
        'minimum_supported_build' => 'integer',
        'file_size' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeLatestBuild($query)
    {
        return $query->orderByDesc('build');
    }

    public function scopeNotOlderThan($query, int $build)
    {
        return $query->where('build', '>', $build);
    }

    public function getIsValidAttribute(): bool
    {
        if (empty($this->version)) return false;
        $parts = explode('.', $this->version);
        if (count($parts) < 2) return false;
        foreach ($parts as $part) {
            if (!is_numeric($part)) return false;
        }
        return $this->build > 0;
    }

    public function toArray()
    {
        return [
            'version' => $this->version,
            'build' => $this->build,
            'download_url' => $this->download_url,
            'force_update' => $this->force_update,
            'release_notes' => $this->release_notes ?? [],
            'release_date' => $this->release_date?->format('Y-m-d'),
            'minimum_supported_version' => $this->minimum_supported_version,
            'minimum_supported_build' => $this->minimum_supported_build,
            'file_size' => $this->file_size,
            'checksum' => $this->checksum,
            'platform' => $this->platform,
        ];
    }
}
