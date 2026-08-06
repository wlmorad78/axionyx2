<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Company\Company;
use App\Traits\BelongsToCompany;

class NotificationGroup extends Model
{
    use HasFactory, BelongsToCompany;

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
