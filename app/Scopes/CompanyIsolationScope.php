<?php

namespace App\Scopes;

use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that automatically isolates model queries by company.
 *
 * When applied to a model, all queries will be scoped to the current company
 * resolved via CompanyContext::id().
 *
 * Models can bypass this scope using:
 *   $query->withoutGlobalScope(CompanyIsolationScope::class)
 *   Model::withoutGlobalScope(CompanyIsolationScope::class)->...
 */
class CompanyIsolationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = CompanyContext::id();

        if ($companyId !== null) {
            $builder->where('company_id', $companyId);
        }
    }

    /**
     * Allow models to be queried without company isolation when needed
     * (e.g., admin screens or reports that aggregate across all companies).
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutCompanyScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(CompanyIsolationScope::class);
        });

        $builder->macro('forAllCompanies', function (Builder $builder) {
            return $builder->withoutGlobalScope(CompanyIsolationScope::class);
        });
    }
}
