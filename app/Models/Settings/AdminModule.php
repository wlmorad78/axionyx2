<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminModule extends Model
{
    protected $fillable = [
        'key',
        'title',
        'icon',
        'sort_order',
    ];

    public function screens(): HasMany
    {
        return $this->hasMany(AdminScreen::class, 'module_id');
    }
}
