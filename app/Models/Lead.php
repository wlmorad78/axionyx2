<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_code',
        'lead_name',
        'mobile',
        'email',
        'source',
        'status',
    ];

    public function activities()
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }
}
