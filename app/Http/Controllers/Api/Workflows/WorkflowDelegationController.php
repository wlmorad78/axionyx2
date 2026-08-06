<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDelegation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowDelegationController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowDelegation::query()->with(['fromUser', 'toUser']);

        if ($request->filled('from_user_id')) $query->where('from_user_id', $request->from_user_id);
        if ($request->filled('to_user_id')) $query->where('to_user_id', $request->to_user_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_delegation', 'create'));
        $workflowDelegation = WorkflowDelegation::create($data);
        return response()->json($workflowDelegation, 201);
    }

    public function show($id)
    {
        return WorkflowDelegation::with(['fromUser', 'toUser'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowDelegation = WorkflowDelegation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_delegation', 'update', $workflowDelegation));
        $workflowDelegation->update($data);
        return $workflowDelegation;
    }

    public function destroy($id)
    {
        $workflowDelegation = WorkflowDelegation::findOrFail($id);
        $workflowDelegation->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
