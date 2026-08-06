<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDataRequest::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%{$s}%")
                  ->orWhere('entity_type', 'like', "%{$s}%")
                  ->orWhere('request_action', 'like', "%{$s}%")
                  ->orWhere('current_status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('current_status')) $query->where('current_status', $request->current_status);
        if ($request->filled('request_action')) $query->where('request_action', $request->request_action);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request', 'create'));
        $masterDataRequest = MasterDataRequest::create($data);
        return response()->json($masterDataRequest, 201);
    }

    public function show($id)
    {
        return MasterDataRequest::with(['steps', 'history', 'requestType', 'requestedBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $masterDataRequest = MasterDataRequest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request', 'update', $masterDataRequest));
        $masterDataRequest->update($data);
        return $masterDataRequest;
    }

    public function destroy($id)
    {
        $masterDataRequest = MasterDataRequest::findOrFail($id);
        $masterDataRequest->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $masterDataRequest = MasterDataRequest::withTrashed()->findOrFail($id);
        $masterDataRequest->restore();
        return $masterDataRequest;
    }

    public function forceDelete($id)
    {
        $masterDataRequest = MasterDataRequest::withTrashed()->findOrFail($id);
        $masterDataRequest->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
