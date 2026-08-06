<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * Resolves the current branch ID from any source.
 * Single source of truth for branch-level scoping.
 */
class BranchContext
{
    protected static ?int $overriddenBranchId = null;

    /**
     * Get the current branch ID.
     * Tries in order: override → request → header → authenticated user's default
     */
    public static function id(): ?int
    {
        // 1. Programmatic override (tests, queue, etc.)
        if (static::$overriddenBranchId !== null) {
            return static::$overriddenBranchId;
        }

        // 2. Request (set by BranchScope middleware)
        $requestBranch = Request::input('branch_id');
        if ($requestBranch && is_numeric($requestBranch)) {
            return (int) $requestBranch;
        }

        // 3. HTTP header (API clients)
        $header = Request::header('X-Branch-Id');
        if ($header && is_numeric($header)) {
            return (int) $header;
        }

        // 4. Authenticated user's default branch
        $user = Request::user() ?? \Auth::user();
        if ($user) {
            $defaultBranch = DB::table('user_branches')
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->first();

            if ($defaultBranch) {
                return (int) $defaultBranch->branch_id;
            }

            // 5. First available branch
            $firstBranch = DB::table('user_branches')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->first();

            if ($firstBranch) {
                return (int) $firstBranch->branch_id;
            }
        }

        return null;
    }

    /**
     * Override branch_id for testing or queue jobs.
     */
    public static function override(int $branchId): void
    {
        static::$overriddenBranchId = $branchId;
    }

    /**
     * Clear the override.
     */
    public static function clear(): void
    {
        static::$overriddenBranchId = null;
    }

    /**
     * Run a closure within a specific branch context.
     */
    public static function runAs(int $branchId, callable $callback): mixed
    {
        static::override($branchId);
        try {
            return $callback();
        } finally {
            static::clear();
        }
    }
}
