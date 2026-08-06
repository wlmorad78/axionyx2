<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\ApprovalAction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApprovalActionController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalAction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('approval_action', 'create'));
        $approvalAction = ApprovalAction::create($data);
        return response()->json($approvalAction, 201);
    }

    public function show($id)
    {
        return ApprovalAction::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $approvalAction = ApprovalAction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('approval_action', 'update', $approvalAction));
        $approvalAction->update($data);
        return $approvalAction;
    }

    public function destroy($id)
    {
        $approvalAction = ApprovalAction::findOrFail($id);
        $approvalAction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $approvalAction = ApprovalAction::withTrashed()->findOrFail($id);
        $approvalAction->restore();
        return $approvalAction;
    }

    public function forceDelete($id)
    {
        $approvalAction = ApprovalAction::withTrashed()->findOrFail($id);
        $approvalAction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
