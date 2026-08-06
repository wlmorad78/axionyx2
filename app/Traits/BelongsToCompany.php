<?php

namespace App\Traits;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Add this trait to any Eloquent model that belongs to a company.
 * 
 * Usage:
 *   class MyModel extends Model {
 *       use \App\Traits\BelongsToCompany;
 *   }
 * 
 * Company source is resolved via CompanyContext::id() which can be:
 * - HTTP header (X-Company-Id)
 * - Authenticated user's company_id
 * - Programmatic override (tests, queue jobs)
 * - Future: Subdomain, JWT claim, API token, etc.
 */
trait BelongsToCompany
{
    /**
     * Scope: only rows belonging to the current company.
     * Pass $companyId explicitly or let CompanyContext resolve it.
     */
    public function scopeForCompany(Builder $query, ?int $companyId = null): Builder
    {
        $companyId ??= CompanyContext::id();

        if ($companyId === null) {
            return $query; // no scope — shared/global
        }

        return $query->where('company_id', $companyId);
    }

    /**
     * Auto-set company_id on creating if not set.
     */
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (is_null($model->company_id)) {
                $model->company_id = CompanyContext::id();
            }
        });
    }
}
