<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;

class ActivityLog extends Model
{
    use BelongsToCompany;

    public $timestamps = false;
    protected $fillable = [
        'user_id', 'company_id', 'type', 'description',
        'subject_type', 'subject_id', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Log an activity.
     */
    public static function log(string $type, string $description, $subject = null, ?int $userId = null, ?int $companyId = null, ?array $metadata = null, ?string $ip = null): static
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'company_id' => $companyId ?? auth()->user()?->company_id,
            'type' => $type,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'metadata' => $metadata,
            'ip_address' => $ip ?? request()->ip(),
        ]);
    }
}
