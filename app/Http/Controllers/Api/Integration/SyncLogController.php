<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SyncLog};
use App\Support\ValidationRules;

class SyncLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SyncLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('table_name', 'like', "%{$s}%")
                  ->orWhere('operation', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sync_log', 'create'));
        $syncLog = SyncLog::create($data);
        return response()->json($syncLog, 201);
    }

    public function show($id)
    {
        return SyncLog::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $syncLog = SyncLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sync_log', 'update', $syncLog));
        $syncLog->update($data);
        return $syncLog;
    }

    public function destroy($id)
    {
        $syncLog = SyncLog::findOrFail($id);
        $syncLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $syncLog = SyncLog::withTrashed()->findOrFail($id);
        $syncLog->restore();
        return $syncLog;
    }

    public function forceDelete($id)
    {
        $syncLog = SyncLog::withTrashed()->findOrFail($id);
        $syncLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
