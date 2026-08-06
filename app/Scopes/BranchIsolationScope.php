<?php

namespace App\Scopes;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that automatically isolates model queries by branch.
 *
 * When applied to a model, all queries will be scoped to the current branch
 * resolved via BranchContext::id().
 *
 * Models can bypass this scope using:
 *   $query->withoutGlobalScope(BranchIsolationScope::class)
 *   Model::withoutGlobalScope(BranchIsolationScope::class)->...
 */
class BranchIsolationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = BranchContext::id();

        if ($branchId !== null) {
            $builder->where('branch_id', $branchId);
        }
    }

    /**
     * Allow models to be queried without branch isolation when needed.
     * e.g., for reports that aggregate across all branches.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutBranchScope', function (Builder $builder) {
            return $builder->withoutGlobalScope(BranchIsolationScope::class);
        });

        $builder->macro('forAllBranches', function (Builder $builder) {
            return $builder->withoutGlobalScope(BranchIsolationScope::class);
        });
    }
}
