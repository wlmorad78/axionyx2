<?php

namespace App\Services;

use App\Models\AdminModule;
use App\Models\AdminScreen;
use App\Models\User;
use Illuminate\Support\Collection;

class AdminNavigationService
{
    public function __construct(private AuthorizationService $authz) {}

    public function forUser(User $user): array
    {
        $user->loadMissing('roles');

        $screens = $this->resolveScreens($user);
        $screenIds = $screens->pluck('id');

        $modules = AdminModule::query()
            ->where('is_active', true)
            ->whereHas('screens', fn ($q) => $q->whereIn('id', $screenIds)->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->with([
                'screens' => fn ($q) => $q
                    ->whereIn('id', $screenIds)
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with([
                        'children' => fn ($childQ) => $childQ
                            ->whereIn('id', $screenIds)
                            ->where('is_active', true)
                            ->orderBy('sort_order'),
                    ]),
            ])
            ->get();

        return [
            'modules' => $modules->map(fn (AdminModule $module) => [
                'id' => $module->id,
                'key' => $module->key,
                'title' => $module->title,
                'icon' => $module->icon,
                'screens' => $module->screens->map(fn (AdminScreen $screen) => $this->formatScreen($screen))->values(),
            ])->values(),
        ];
    }

    private function resolveScreens(User $user): Collection
    {
        if ($this->authz->isAdmin($user)) {
            return AdminScreen::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        $roleIds = $user->roles->pluck('id');

        return AdminScreen::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->orderBy('sort_order')
            ->get();
    }

    private function formatScreen(AdminScreen $screen): array
    {
        return [
            'id' => $screen->id,
            'key' => $screen->key,
            'title' => $screen->title,
            'icon' => $screen->icon,
            'route' => $screen->route,
            'api_resource' => $screen->api_resource,
            'screen_type' => $screen->screen_type,
            'children' => $screen->children
                ->map(fn (AdminScreen $child) => $this->formatScreen($child))
                ->values(),
        ];
    }
}
