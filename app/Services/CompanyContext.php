<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Request;

/**
 * Resolves the current company ID from any source.
 * Single source of truth for multi-tenancy.
 *
 * Future sources: Subdomain, JWT claim, API token, session, etc.
 */
class CompanyContext
{
    protected static ?int $overriddenCompanyId = null;

    /**
     * Get the current company ID.
     * Tries in order: override → header → authenticated user
     */
    public static function id(): ?int
    {
        // 1. Programmatic override (tests, queue, etc.)
        if (static::$overriddenCompanyId !== null) {
            return static::$overriddenCompanyId;
        }

        // 2. HTTP header (API clients)
        $header = Request::header('X-Company-Id');
        if ($header && is_numeric($header)) {
            return (int) $header;
        }

        // 3. Authenticated user's company
        $user = Request::user() ?? \Auth::user();
        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }

    /**
     * Override company_id for testing or queue jobs.
     */
    public static function override(int $companyId): void
    {
        static::$overriddenCompanyId = $companyId;
    }

    /**
     * Clear the override.
     */
    public static function clear(): void
    {
        static::$overriddenCompanyId = null;
    }

    /**
     * Run a closure within a specific company context.
     */
    public static function runAs(int $companyId, callable $callback): mixed
    {
        static::override($companyId);
        try {
            return $callback();
        } finally {
            static::clear();
        }
    }
}
