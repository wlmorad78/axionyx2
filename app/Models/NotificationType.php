<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type_code',
        'type_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function templates()
    {
        return $this->hasMany(NotificationTemplate::class);
    }
}
