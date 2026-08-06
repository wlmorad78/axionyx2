<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{SyncBatch};
use App\Support\ValidationRules;

class SyncBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = SyncBatch::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('device_id', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sync_batch', 'create'));
        $syncBatch = SyncBatch::create($data);
        return response()->json($syncBatch, 201);
    }

    public function show($id)
    {
        return SyncBatch::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $syncBatch = SyncBatch::findOrFail($id);
        $data = $request->validate(ValidationRules::for('sync_batch', 'update', $syncBatch));
        $syncBatch->update($data);
        return $syncBatch;
    }

    public function destroy($id)
    {
        $syncBatch = SyncBatch::findOrFail($id);
        $syncBatch->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $syncBatch = SyncBatch::withTrashed()->findOrFail($id);
        $syncBatch->restore();
        return $syncBatch;
    }

    public function forceDelete($id)
    {
        $syncBatch = SyncBatch::withTrashed()->findOrFail($id);
        $syncBatch->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
