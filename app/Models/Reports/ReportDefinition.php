<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Company\Company;
use App\Models\User;

class ReportDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'category',
        'company_id',
        'created_by',
        'base_table',
        'selected_columns',
        'filters',
        'sort_by',
        'group_by',
        'aggregations',
        'chart_config',
        'is_public',
        'is_template',
        'is_enabled',
        'sort_order',
        'last_run_at',
        'run_count',
    ];

    protected $casts = [
        'selected_columns' => 'array',
        'filters' => 'array',
        'sort_by' => 'array',
        'group_by' => 'array',
        'aggregations' => 'array',
        'chart_config' => 'array',
        'is_public' => 'boolean',
        'is_template' => 'boolean',
        'is_enabled' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shares()
    {
        return $this->hasMany(ReportShare::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'report_shares')
            ->withPivot('permission')
            ->withTimestamps();
    }
}
