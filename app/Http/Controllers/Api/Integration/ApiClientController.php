<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiClientController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiClient::query()->with('tokens', 'permissions');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('client_name', 'like', "%{$s}%")
                    ->orWhere('client_id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_client', 'create'));
        return response()->json(ApiClient::create($data), 201);
    }

    public function show($id) { return ApiClient::with('tokens', 'permissions')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ApiClient::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_client', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ApiClient::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }

    public function restore($id) { $m = ApiClient::withTrashed()->findOrFail($id); $m->restore(); return $m; }

    public function forceDelete($id) { ApiClient::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
