<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequestHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDataRequestHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('action_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('master_data_request_id')) $query->where('master_data_request_id', $request->master_data_request_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request_history', 'create'));
        $masterDataRequestHistory = MasterDataRequestHistory::create($data);
        return response()->json($masterDataRequestHistory, 201);
    }

    public function show($id)
    {
        return MasterDataRequestHistory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request_history', 'update', $masterDataRequestHistory));
        $masterDataRequestHistory->update($data);
        return $masterDataRequestHistory;
    }

    public function destroy($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::findOrFail($id);
        $masterDataRequestHistory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::withTrashed()->findOrFail($id);
        $masterDataRequestHistory->restore();
        return $masterDataRequestHistory;
    }

    public function forceDelete($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::withTrashed()->findOrFail($id);
        $masterDataRequestHistory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
