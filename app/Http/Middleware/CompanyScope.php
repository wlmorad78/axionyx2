<?php

namespace App\Http\Middleware;

use App\Services\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CompanyScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            $token = $request->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                    if ($user) {
                        Auth::setUser($user);
                    }
                }
            }
        }

        if ($user) {
            $headerCompanyId = $request->header('X-Company-Id');

            if ($headerCompanyId && is_numeric($headerCompanyId)) {
                CompanyContext::override((int) $headerCompanyId);
                if (!$request->filled('company_id')) {
                    $request->merge(['company_id' => (int) $headerCompanyId]);
                }
            } elseif ($user->company_id && !$request->filled('company_id')) {
                CompanyContext::override((int) $user->company_id);
                $request->merge(['company_id' => (int) $user->company_id]);
            }

            $salesmanId = $request->header('X-Salesman-Id');
            if ($salesmanId && is_numeric($salesmanId)) {
                $request->merge(['_salesman_id' => (int) $salesmanId]);
            }
        }

        return $next($request);
    }
}
