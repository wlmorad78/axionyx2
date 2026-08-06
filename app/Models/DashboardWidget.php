<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'category',
        'widget_type',
        'description',
        'is_active',
        'default_sort_order',
        'default_width',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_sort_order' => 'integer',
        'default_width' => 'integer',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_widgets', 'dashboard_widget_id', 'role_id')
            ->withPivot(['is_visible', 'sort_order', 'width', 'config'])
            ->withTimestamps();
    }
}
