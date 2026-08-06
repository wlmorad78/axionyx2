<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NotificationQueue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notification_queue';

    protected $fillable = [
        'notification_id',
        'notification_template_id',
        'channel_id',
        'user_id',
        'status',
        'scheduled_at',
        'sent_at',
        'attempt_count',
        'last_attempt_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function notificationTemplate()
    {
        return $this->belongsTo(NotificationTemplate::class);
    }

    public function channel()
    {
        return $this->belongsTo(NotificationChannel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
