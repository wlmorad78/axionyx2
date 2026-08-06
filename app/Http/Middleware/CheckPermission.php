<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(protected PermissionService $permissions) {}

    /**
     * Manual permission check — pass permission strings as arguments.
     * Example: Route::middleware('permission:sales.invoice.post')
     */
    public function handle(Request $request, Closure $next, string ...$permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$this->permissions->checkAny($user, $permission)) {
            return response()->json([
                'message' => 'Unauthorized: insufficient permissions',
                'required' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
