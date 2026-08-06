<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NotificationGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_group_id',
        'user_id',
    ];

    public function group()
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
