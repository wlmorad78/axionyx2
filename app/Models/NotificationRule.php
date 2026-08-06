<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'notification_event_id',
        'notification_template_id',
        'priority',
        'send_immediately',
        'is_active',
    ];

    protected $casts = [
        'send_immediately' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function event()
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRuleRecipient::class);
    }
}
