<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;

class EventLog extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'event_definition_id',
        'company_id',
        'user_id',
        'payload',
        'status',
        'processed_by',
        'fired_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_by' => 'array',
        'fired_at' => 'datetime',
    ];

    public function eventDefinition()
    {
        return $this->belongsTo(EventDefinition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
