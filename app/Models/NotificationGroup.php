<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'group_code',
        'group_name',
        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function members()
    {
        return $this->hasMany(NotificationGroupMember::class);
    }
}
