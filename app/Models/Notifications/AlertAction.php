<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AlertAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'action_by',
        'action_type',
        'notes',
        'action_date',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by');
    }
}
