<?php

namespace App\Traits;

use App\Scopes\BranchIsolationScope;
use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Add this trait to any Eloquent model that belongs to a branch.
 *
 * Usage:
 *   class MyModel extends Model {
 *       use \App\Traits\BranchScoped;
 *   }
 *
 * This trait:
 * - Auto-applies BranchIsolationScope (global scope) for automatic branch filtering
 * - Auto-sets branch_id on creating if not set
 * - Provides scopeForBranch() for explicit branch filtering
 * - Provides withoutBranchScope() macro to bypass isolation when needed
 */
trait BranchScoped
{
    /**
     * Boot the BranchScoped trait.
     * Registers the global scope and the creating event.
     */
    public static function bootBranchScoped(): void
    {
        // Auto-apply branch isolation global scope
        static::addGlobalScope(new BranchIsolationScope);

        // Auto-set branch_id on creating if not set
        static::creating(function ($model) {
            if (is_null($model->branch_id)) {
                $model->branch_id = BranchContext::id();
            }
        });
    }

    /**
     * Scope: only rows belonging to the current branch.
     * Pass $branchId explicitly or let BranchContext resolve it.
     */
    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        $branchId ??= BranchContext::id();

        if ($branchId === null) {
            return $query; // no scope — shared/global
        }

        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope: bypass branch isolation for this query.
     * Use for reports or admin views that need all branches.
     */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchIsolationScope::class);
    }

    /**
     * Scope: query across all branches.
     * Alias for withoutBranchScope().
     */
    public function scopeForAllBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchIsolationScope::class);
    }
}
