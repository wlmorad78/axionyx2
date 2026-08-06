<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DistributionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'plan_no', 'plan_name', 'plan_date',
        'history_months', 'allocation_factor', 'enforce_plan_limit',
        'total_quantity', 'total_demand', 'units_per_carton',
        'status', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'approved_at' => 'datetime',
        'allocation_factor' => 'decimal:4',
        'enforce_plan_limit' => 'boolean',
        'total_quantity' => 'decimal:2',
        'total_demand' => 'decimal:2',
        'units_per_carton' => 'integer',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function createdByEmployee() { return $this->belongsTo(Employee::class, 'created_by'); }
    public function approvedByEmployee() { return $this->belongsTo(Employee::class, 'approved_by'); }
    public function products() { return $this->hasMany(DistributionPlanProduct::class); }
    public function reps() { return $this->hasMany(DistributionPlanRep::class); }

    protected static function booted(): void
    {
        static::creating(function (DistributionPlan $model) {
            if (!$model->plan_no) {
                $lastPlanNo = static::where('company_id', $model->company_id)
                    ->whereNotNull('plan_no')
                    ->max(DB::raw("CAST(SUBSTR(plan_no, 4) AS INTEGER)"));
                $next = ($lastPlanNo ?? 0) + 1;
                $model->plan_no = 'DP-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
