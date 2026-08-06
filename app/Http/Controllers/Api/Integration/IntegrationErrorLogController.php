<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationErrorLog;
use Illuminate\Http\Request;

class IntegrationErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationErrorLog::query()->with('account');
        if ($request->filled('integration_account_id')) $query->where('integration_account_id', $request->integration_account_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return IntegrationErrorLog::with('account')->findOrFail($id); }
}
