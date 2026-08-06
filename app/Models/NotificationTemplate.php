<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_code',
        'template_name',
        'company_id',
        'notification_type_id',
        'channel_id',
        'language_code',
        'title',
        'subject',
        'message_body',
        'channel',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function notificationType()
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function channel()
    {
        return $this->belongsTo(NotificationChannel::class);
    }

    public function queues()
    {
        return $this->hasMany(NotificationQueue::class);
    }
}
