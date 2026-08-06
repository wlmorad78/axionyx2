<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * GET /api/menu/sidebar
     * Returns the filtered sidebar menu for the authenticated user.
     */
    public function sidebar(Request $request, MenuService $menuService)
    {
        $companyId = $request->header('X-Company-Id') ?? $request->query('company_id');
        $menu = $menuService->buildMenu($request->user(), $companyId ? (int) $companyId : null);

        return response()->json([
            'data' => $menu,
        ]);
    }
}
