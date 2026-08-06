<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_instance_id',
        'user_id',
        'notification_type',
        'sent_at',
        'status',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
