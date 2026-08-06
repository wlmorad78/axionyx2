<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRuleRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_rule_id',
        'recipient_type',
        'recipient_value',
    ];

    public function notificationRule()
    {
        return $this->belongsTo(NotificationRule::class);
    }
}
