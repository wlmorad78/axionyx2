<?php

namespace App\Services;

use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthorizationService
{
    public function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleNames::ADMIN) || $user->hasRole('super_admin');
    }

    public function can(User $user, string $resource, string $action): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $roleNames = $user->roles->pluck('name');

        foreach ($roleNames as $roleName) {
            $permissions = config("api_permissions.resources.{$roleName}", []);

            if (isset($permissions['*'])) {
                return true;
            }

            $allowed = $permissions[$resource] ?? [];

            if (in_array('*', $allowed, true) || in_array($action, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    public function resolveResource(Request $request): string
    {
        $route = $request->route();
        $name = $route?->getName() ?? '';

        if (preg_match('/^([\w-]+)\./', $name, $matches)) {
            return $matches[1];
        }

        $uri = trim($request->path(), '/');
        $uri = Str::after($uri, 'api/');

        foreach (['force-delete', 'restore', 'approve', 'submit', 'confirm'] as $suffix) {
            if (Str::contains($uri, "/{$suffix}")) {
                $uri = Str::before($uri, "/{$suffix}");
                break;
            }
        }

        if (preg_match('/^([\w-]+)\/\d+$/', $uri, $matches)) {
            return $matches[1];
        }

        return Str::before($uri, '/');
    }

    public function resolveAction(Request $request): string
    {
        $uri = $request->path();

        if (Str::endsWith($uri, '/dispatch')) {
            return 'dispatch';
        }
        if (Str::endsWith($uri, '/approve')) {
            return 'approve';
        }
        if (Str::endsWith($uri, '/submit')) {
            return 'submit';
        }
        if (Str::endsWith($uri, '/confirm')) {
            return 'confirm';
        }
        if (Str::endsWith($uri, '/restore')) {
            return 'restore';
        }
        if (Str::endsWith($uri, '/force-delete')) {
            return 'force-delete';
        }

        $hasIdParam = collect($request->route()?->parameters() ?? [])
            ->filter(fn ($v) => is_numeric($v) || (is_string($v) && ctype_digit($v)))
            ->isNotEmpty();

        return match ($request->method()) {
            'GET' => $hasIdParam ? 'show' : 'index',
            'POST' => 'store',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'destroy',
            default => 'index',
        };
    }

    public function applyScope(Builder $query, User $user, string $resource): Builder
    {
        if ($this->isAdmin($user)) {
            return $query;
        }

        if (! $user->hasRole(RoleNames::SALES_REP) && ! $user->hasRole(RoleNames::SALES_MAN)) {
            return $query;
        }

        $repId = $user->representative?->id;

        if (! $repId) {
            return $query->whereRaw('1 = 0');
        }

        $scopeColumn = config("api_permissions.representative_scopes.{$resource}");

        if ($scopeColumn === 'user_id') {
            return $query->where($scopeColumn, $user->id);
        }

        if ($scopeColumn) {
            return $query->where($scopeColumn, $repId);
        }

        $selfColumn = config("api_permissions.representative_self.{$resource}");

        if ($selfColumn) {
            return $query->where($selfColumn, $repId);
        }

        $parentScope = config("api_permissions.parent_scopes.{$resource}");

        if ($parentScope) {
            return $this->applyNestedScope($query, $parentScope['relation'], $parentScope['column'], $repId);
        }

        return $query;
    }

    private function applyNestedScope(Builder $query, string $relationPath, string $column, mixed $value): Builder
    {
        $parts = explode('.', $relationPath);
        $first = array_shift($parts);

        return $query->whereHas($first, function (Builder $q) use ($parts, $column, $value) {
            if ($parts === []) {
                $q->where($column, $value);

                return;
            }

            $this->applyNestedScope($q, implode('.', $parts), $column, $value);
        });
    }

    public function authorizeResourceAccess(User $user, string $resource, object $model): void
    {
        if ($this->isAdmin($user) || (! $user->hasRole(RoleNames::SALES_REP) && ! $user->hasRole(RoleNames::SALES_MAN))) {
            return;
        }

        $repId = $user->representative?->id;

        if (! $repId) {
            abort(403, 'غير مصرح');
        }

        $scopeColumn = config("api_permissions.representative_scopes.{$resource}");

        if ($scopeColumn === 'user_id' && (int) $model->{$scopeColumn} !== $user->id) {
            abort(403, 'غير مصرح بالوصول لهذا السجل');
        }

        if ($scopeColumn && isset($model->{$scopeColumn}) && (int) $model->{$scopeColumn} !== $repId) {
            abort(403, 'غير مصرح بالوصول لهذا السجل');
        }

        $selfColumn = config("api_permissions.representative_self.{$resource}");

        if ($selfColumn && (int) $model->{$selfColumn} !== $repId) {
            abort(403, 'غير مصرح بالوصول لهذا السجل');
        }

        $parentScope = config("api_permissions.parent_scopes.{$resource}");

        if ($parentScope) {
            $parent = $this->resolveNestedRelation($model, $parentScope['relation']);

            if (! $parent || (int) $parent->{$parentScope['column']} !== $repId) {
                abort(403, 'غير مصرح بالوصول لهذا السجل');
            }
        }
    }

    private function resolveNestedRelation(object $model, string $relationPath): ?object
    {
        $current = $model;

        foreach (explode('.', $relationPath) as $relation) {
            if (! method_exists($current, $relation)) {
                return null;
            }

            $current = $current->{$relation};

            if (! $current) {
                return null;
            }
        }

        return $current;
    }
}
