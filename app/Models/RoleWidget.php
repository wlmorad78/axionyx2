<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleWidget extends Model
{
    protected $fillable = [
        'role_id',
        'dashboard_widget_id',
        'is_visible',
        'sort_order',
        'width',
        'config',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'width' => 'integer',
        'config' => 'array',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function widget()
    {
        return $this->belongsTo(DashboardWidget::class);
    }
}
