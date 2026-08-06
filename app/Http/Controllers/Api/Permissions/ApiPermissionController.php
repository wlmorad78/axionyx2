<?php

namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\ApiPermission;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiPermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiPermission::query()->with('client');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('resource_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_permission', 'create'));
        return response()->json(ApiPermission::create($data), 201);
    }

    public function show($id) { return ApiPermission::with('client')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ApiPermission::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_permission', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ApiPermission::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
