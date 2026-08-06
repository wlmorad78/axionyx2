<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_id',
        'group',
        'key',
        'value',
        'type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a setting value for a company.
     */
    public static function get(int $companyId, string $group, string $key, mixed $default = null): mixed
    {
        $setting = static::where('company_id', $companyId)
            ->where('group', $group)
            ->where('key', $key)
            ->first();

        if (!$setting) return $default;

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value for a company.
     */
    public static function set(int $companyId, string $group, string $key, mixed $value, string $type = 'string'): static
    {
        $encodedValue = is_array($value) ? json_encode($value) : (string) $value;

        return static::updateOrCreate(
            ['company_id' => $companyId, 'group' => $group, 'key' => $key],
            ['value' => $encodedValue, 'type' => $type]
        );
    }

    /**
     * Get all settings for a company, grouped.
     */
    public static function getAll(int $companyId): array
    {
        $settings = static::where('company_id', $companyId)->get();
        $grouped = [];
        foreach ($settings as $s) {
            $decoded = match ($s->type) {
                'boolean' => (bool) $s->value,
                'integer' => (int) $s->value,
                'decimal' => (float) $s->value,
                'json' => json_decode($s->value, true),
                default => $s->value,
            };
            $grouped[$s->group][$s->key] = $decoded;
        }
        return $grouped;
    }
}
