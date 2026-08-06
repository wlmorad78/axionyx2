<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company\Branch;
use App\Models\Company\Company;
use App\Models\CRM\CustomerVisit;

class Route extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'sales_territory_id',
        'employee_id',
        'code',
        'name_ar',
        'name_en',
        'description',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Route $route) {
            if (! $route->isForceDeleting()) {
                $route->schedules()->delete();
                $route->customers()->delete();
            }
        });

        static::restoring(function (Route $route) {
            $route->schedules()->onlyTrashed()->restore();
            $route->customers()->onlyTrashed()->restore();
        });

        static::forceDeleting(function (Route $route) {
            $route->schedules()->forceDelete();
            $route->customers()->forceDelete();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesTerritory(): BelongsTo
    {
        return $this->belongsTo(SalesTerritory::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\HR\Employee::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(RouteSchedule::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(RouteCustomer::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(CustomerVisit::class);
    }
}
