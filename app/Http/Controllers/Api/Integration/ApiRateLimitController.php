<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiRateLimit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiRateLimitController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiRateLimit::query()->with('client');
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_rate_limit', 'create'));
        return response()->json(ApiRateLimit::create($data), 201);
    }

    public function show($id) { return ApiRateLimit::with('client')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ApiRateLimit::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_rate_limit', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ApiRateLimit::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
