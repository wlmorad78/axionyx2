<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function __construct(private AuthorizationService $authz) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->authz->isAdmin($request->user())) {
            abort(403, 'هذه العملية للمدير فقط');
        }

        return $next($request);
    }
}
