<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Settings\DashboardWidget;
use App\Models\Role;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardWidgetController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => DashboardWidget::orderBy('default_sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:dashboard_widgets',
            'name' => 'required|string',
            'name_ar' => 'nullable|string',
            'category' => 'nullable|string',
            'widget_type' => 'nullable|string',
            'description' => 'nullable|string',
            'default_sort_order' => 'nullable|integer',
            'default_width' => 'nullable|integer|min:1|max:3',
        ]);

        $widget = DashboardWidget::create($validated);
        return response()->json(['data' => $widget], 201);
    }

    public function show(DashboardWidget $widget)
    {
        return response()->json(['data' => $widget]);
    }

    public function update(Request $request, DashboardWidget $widget)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'name_ar' => 'nullable|string',
            'category' => 'nullable|string',
            'widget_type' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'default_sort_order' => 'nullable|integer',
            'default_width' => 'nullable|integer|min:1|max:3',
        ]);

        $widget->update($validated);
        return response()->json(['data' => $widget]);
    }

    public function destroy(DashboardWidget $widget)
    {
        $widget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * GET /api/dashboard/widgets/role/{roleId}
     * Get configured widgets for a role.
     */
    public function roleWidgets(Role $role)
    {
        $widgets = $role->widgets()
            ->where('is_active', true)
            ->orderBy('role_widgets.sort_order')
            ->get();

        return response()->json(['data' => $widgets]);
    }

    /**
     * POST /api/dashboard/widgets/role/{roleId}
     * Sync widgets for a role.
     */
    public function syncRoleWidgets(Request $request, Role $role)
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|integer|exists:dashboard_widgets,id',
            'widgets.*.is_visible' => 'nullable|boolean',
            'widgets.*.sort_order' => 'nullable|integer',
            'widgets.*.width' => 'nullable|integer|min:1|max:3',
            'widgets.*.config' => 'nullable|array',
        ]);

        $syncData = [];
        foreach ($validated['widgets'] as $w) {
            $syncData[$w['id']] = [
                'is_visible' => $w['is_visible'] ?? true,
                'sort_order' => $w['sort_order'] ?? 0,
                'width' => $w['width'] ?? 1,
                'config' => $w['config'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $role->widgets()->sync($syncData);

        return response()->json(['message' => 'Widgets synced']);
    }

    /**
     * GET /api/dashboard
     * Build dashboard for authenticated user.
     */
    public function dashboard(Request $request, DashboardService $dashboardService)
    {
        $data = $dashboardService->buildDashboard($request->user());
        return response()->json(['data' => $data]);
    }
}
