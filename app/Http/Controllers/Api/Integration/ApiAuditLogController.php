<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiAuditLog;
use Illuminate\Http\Request;

class ApiAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiAuditLog::query()->with('client');
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return ApiAuditLog::with('client')->findOrFail($id); }
}
