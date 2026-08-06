<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySidebarSetting extends Model
{
    protected $fillable = [
        'company_id',
        'menu_key',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
