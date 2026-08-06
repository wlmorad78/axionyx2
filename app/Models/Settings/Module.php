<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'version',
        'status',
        'is_core',
        'is_enabled',
        'dependencies',
        'capabilities',
        'config',
        'description',
        'description_ar',
        'author',
        'path',
        'sort_order',
        'installed_at',
        'enabled_at',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_enabled' => 'boolean',
        'dependencies' => 'array',
        'capabilities' => 'array',
        'config' => 'array',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];

    // ─── Scopes ────────────────────────────────────────

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installed');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    // ─── Relationships ─────────────────────────────────

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'module_features', 'module_id', 'feature_id')
            ->withPivot(['is_enabled'])
            ->withTimestamps();
    }

    public function widgets()
    {
        return $this->belongsToMany(DashboardWidget::class, 'module_widgets', 'module_id', 'dashboard_widget_id')
            ->withPivot(['is_enabled'])
            ->withTimestamps();
    }

    // ─── Helpers ───────────────────────────────────────

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function hasDependency(string $code): bool
    {
        return in_array($code, $this->dependencies ?? []);
    }

    public function isInstalled(): bool
    {
        return $this->status === 'installed';
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled && $this->status === 'installed';
    }

    public function displayName(bool $isArabic = false): string
    {
        return $isArabic ? ($this->name_ar ?? $this->name) : $this->name;
    }
}
