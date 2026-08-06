<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegrationProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'provider_code',
        'provider_name',
        'provider_type',
        'description',
        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accounts()
    {
        return $this->hasMany(IntegrationAccount::class);
    }

    public function events()
    {
        return $this->hasMany(IntegrationEvent::class);
    }
}
