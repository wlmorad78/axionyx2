<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Role;

class AdminScreen extends Model
{
    protected $fillable = [
        'module_id',
        'key',
        'title',
        'icon',
        'route',
        'api_resource',
        'screen_type',
        'sort_order',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(AdminModule::class, 'module_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_screen_roles', 'screen_id', 'role_id');
    }
}
