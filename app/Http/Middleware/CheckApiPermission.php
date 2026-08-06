<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiPermission
{
    public function __construct(private AuthorizationService $authz) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'يجب تسجيل الدخول');
        }

        $user->loadMissing('roles', 'representative');

        if ($request->is('api/register') && ! $this->authz->isAdmin($user)) {
            abort(403, 'فقط المدير يمكنه إنشاء مستخدمين');
        }

        if (in_array($request->path(), ['api/logout', 'api/me', 'api/admin/navigation'], true)) {
            return $next($request);
        }

        $resource = $this->authz->resolveResource($request);
        $action = $this->authz->resolveAction($request);

        if (! $this->authz->can($user, $resource, $action)) {
            abort(403, 'ليس لديك صلاحية لهذه العملية');
        }

        return $next($request);
    }
}
