<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('endpoint', 'like', "%{$s}%")
                    ->orWhere('method', 'like', "%{$s}%")
                    ->orWhere('status_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_log', 'create'));
        $apiLog = ApiLog::create($data);
        return response()->json($apiLog, 201);
    }

    public function show($id)
    {
        return ApiLog::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $apiLog = ApiLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_log', 'update', $apiLog));
        $apiLog->update($data);
        return $apiLog;
    }

    public function destroy($id)
    {
        $apiLog = ApiLog::findOrFail($id);
        $apiLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $apiLog = ApiLog::withTrashed()->findOrFail($id);
        $apiLog->restore();
        return $apiLog;
    }

    public function forceDelete($id)
    {
        $apiLog = ApiLog::withTrashed()->findOrFail($id);
        $apiLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
