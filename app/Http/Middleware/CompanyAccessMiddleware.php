<?php

namespace App\Http\Middleware;

use App\Models\Company\Company;
use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that the authenticated user is authorized to access the
 * requested company. Runs AFTER CompanyScope (which reads X-Company-Id
 * and sets CompanyContext).
 *
 * Behavior:
 *   - No authenticated user → pass through (auth middleware handles)
 *   - CLI / Queue / No request → pass through (CompanyContext set explicitly)
 *   - Missing company ID + Admin user → pass through (global management endpoints)
 *   - Missing company ID + Non-admin → 400 Bad Request
 *   - Invalid company ID → 404 Not Found
 *   - Super Admin → allow any company
 *   - Regular user + company not in pivot → 403 Forbidden
 *   - Regular user + company in pivot → allow
 */
class CompanyAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $companyId = $this->resolveCompanyId($request);

        if ($companyId === null) {
            if ($user->company_id) {
                $companyId = (int) $user->company_id;
            } elseif ($user->isAdmin()) {
                return $next($request);
            } else {
                return response()->json([
                    'message' => 'Company context required. Send X-Company-Id header or set a default company.',
                ], 400);
            }
        }

        if (!Company::where('id', $companyId)->exists()) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        if ($user->isAdmin()) {
            if (!CompanyContext::id()) {
                CompanyContext::override($companyId);
                if (!$request->filled('company_id')) {
                    $request->merge(['company_id' => $companyId]);
                }
            }

            return $next($request);
        }

        $hasAccess = $user->companies()->where('companies.id', $companyId)->exists();

        if (!$hasAccess) {
            return response()->json([
                'message' => 'Unauthorized. You do not have access to this company.',
            ], 403);
        }

        if (!CompanyContext::id()) {
            CompanyContext::override($companyId);
            if (!$request->filled('company_id')) {
                $request->merge(['company_id' => $companyId]);
            }
        }

        return $next($request);
    }

    private function resolveCompanyId(Request $request): ?int
    {
        $header = $request->header('X-Company-Id');
        if ($header && is_numeric($header)) {
            return (int) $header;
        }

        $input = $request->input('company_id');
        if ($input && is_numeric($input)) {
            return (int) $input;
        }

        return null;
    }
}
