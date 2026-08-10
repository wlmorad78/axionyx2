<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySidebarSetting;
use App\Services\ModuleRegistry;
use Illuminate\Http\Request;

class CompanySidebarController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->query('company_id');

        if ($companyId) {
            $settings = CompanySidebarSetting::where('company_id', $companyId)
                ->pluck('is_visible', 'menu_key')
                ->toArray();

            $allMenuKeys = $this->getAllMenuKeys();
            $menu = collect($allMenuKeys)->map(function ($item) use ($settings) {
                $key = $item['key'];
                $isVisible = $settings[$key] ?? true;

                $children = collect($item['children'] ?? [])->map(function ($child) use ($settings) {
                    $childKey = $child['key'];
                    return [
                        'key' => $childKey,
                        'title_en' => $child['title_en'],
                        'title_ar' => $child['title_ar'],
                        'icon' => $child['icon'],
                        'color' => $child['color'],
                        'is_visible' => $settings[$childKey] ?? true,
                    ];
                })->toArray();

                return [
                    'key' => $key,
                    'title_en' => $item['title_en'],
                    'title_ar' => $item['title_ar'],
                    'icon' => $item['icon'],
                    'color' => $item['color'],
                    'is_visible' => $isVisible,
                    'children' => $children,
                ];
            })->toArray();

            return response()->json(['data' => $menu]);
        }

        $allMenuKeys = $this->getAllMenuKeys();
        $companies = Company::select('id', 'name_ar', 'name_en', 'code')->get();

        $result = $companies->map(function ($company) use ($allMenuKeys) {
            $settings = CompanySidebarSetting::where('company_id', $company->id)
                ->pluck('is_visible', 'menu_key')
                ->toArray();

            $menu = collect($allMenuKeys)->map(function ($item) use ($settings) {
                $key = $item['key'];
                $isVisible = $settings[$key] ?? true;

                $children = collect($item['children'] ?? [])->map(function ($child) use ($settings) {
                    $childKey = $child['key'];
                    return [
                        'key' => $childKey,
                        'title_en' => $child['title_en'],
                        'title_ar' => $child['title_ar'],
                        'icon' => $child['icon'],
                        'color' => $child['color'],
                        'is_visible' => $settings[$childKey] ?? true,
                    ];
                })->toArray();

                return [
                    'key' => $key,
                    'title_en' => $item['title_en'],
                    'title_ar' => $item['title_ar'],
                    'icon' => $item['icon'],
                    'color' => $item['color'],
                    'is_visible' => $isVisible,
                    'children' => $children,
                ];
            })->toArray();

            return [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name_ar ?? $company->name_en ?? '',
                    'name_en' => $company->name_en,
                    'code' => $company->code,
                ],
                'menu' => $menu,
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'settings' => 'required|array',
            'settings.*.menu_key' => 'required|string',
            'settings.*.is_visible' => 'required|boolean',
        ]);

        $companyId = $request->company_id;
        $settings = $request->settings;

        foreach ($settings as $item) {
            CompanySidebarSetting::updateOrCreate(
                ['company_id' => $companyId, 'menu_key' => $item['menu_key']],
                ['is_visible' => $item['is_visible']]
            );
        }

        return response()->json(['message' => 'Sidebar settings updated successfully']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        CompanySidebarSetting::where('company_id', $request->company_id)->delete();

        return response()->json(['message' => 'Sidebar settings reset to defaults']);
    }

    public function getEffectiveMenu(Request $request)
    {
        $companyId = $request->query('company_id');
        if (!$companyId) {
            return response()->json(['data' => $this->getAllMenuKeys()]);
        }

        $settings = CompanySidebarSetting::where('company_id', $companyId)
            ->pluck('is_visible', 'menu_key')
            ->toArray();

        $allMenuKeys = $this->getAllMenuKeys();

        $menu = collect($allMenuKeys)->filter(function ($item) use ($settings) {
            $key = $item['key'];
            return $settings[$key] ?? true;
        })->map(function ($item) use ($settings) {
            $children = collect($item['children'] ?? [])->filter(function ($child) use ($settings) {
                $childKey = $child['key'];
                return $settings[$childKey] ?? true;
            })->values()->toArray();

            return [
                ...$item,
                'children' => $children,
            ];
        })->values()->toArray();

        return response()->json(['data' => $menu]);
    }

    private function getAllMenuKeys(): array
    {
        $base = config('menu.items', []);
        $moduleMenus = ModuleRegistry::getMenuItems();

        foreach ($moduleMenus as $moduleItem) {
            if (!is_array($moduleItem) || empty($moduleItem['key'])) continue;

            $key = $moduleItem['key'];
            $existingIndex = null;
            foreach ($base as $i => $item) {
                if (is_array($item) && ($item['key'] ?? null) === $key) {
                    $existingIndex = $i;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $existingChildren = $base[$existingIndex]['children'] ?? [];
                $newChildren = $moduleItem['children'] ?? [];
                foreach ($newChildren as $child) {
                    if (!is_array($child) || empty($child['key'])) continue;
                    $childKey = $child['key'];
                    $found = false;
                    foreach ($existingChildren as $ec) {
                        if (is_array($ec) && ($ec['key'] ?? null) === $childKey) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $existingChildren[] = $child;
                    }
                }
                $base[$existingIndex]['children'] = $existingChildren;
            } else {
                $base[] = $moduleItem;
            }
        }

        return $base;
    }
}
