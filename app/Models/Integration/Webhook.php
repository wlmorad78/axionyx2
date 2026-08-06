<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Company\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;

class Webhook extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'code', 'name', 'company_id', 'created_by', 'url', 'method',
        'events', 'headers', 'secret', 'is_active', 'retry_count',
        'timeout_seconds', 'success_count', 'failure_count', 'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'wh_' . Str::random(12);
            }
            if (empty($model->secret)) {
                $model->secret = Str::random(40);
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function deliveries() { return $this->hasMany(WebhookDelivery::class); }

    public function matchesEvent(string $eventCode): bool
    {
        if (in_array('*', $this->events ?? [])) return true;
        return in_array($eventCode, $this->events ?? []);
    }
}
