<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;

class Notification extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'company_id',
        'notification_no',
        'notification_type_id',
        'notification_event_id',
        'priority',
        'status',
        'title',
        'message',
        'reference_type',
        'reference_id',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function notificationType()
    {
        return $this->belongsTo(NotificationType::class);
    }

    public function event()
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function deliveries()
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}
