<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\ApprovalRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('reference_type', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('approval_request', 'create'));
        $approvalRequest = ApprovalRequest::create($data);
        return response()->json($approvalRequest, 201);
    }

    public function show($id)
    {
        return ApprovalRequest::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('approval_request', 'update', $approvalRequest));
        $approvalRequest->update($data);
        return $approvalRequest;
    }

    public function destroy($id)
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        $approvalRequest->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $approvalRequest = ApprovalRequest::withTrashed()->findOrFail($id);
        $approvalRequest->restore();
        return $approvalRequest;
    }

    public function forceDelete($id)
    {
        $approvalRequest = ApprovalRequest::withTrashed()->findOrFail($id);
        $approvalRequest->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
