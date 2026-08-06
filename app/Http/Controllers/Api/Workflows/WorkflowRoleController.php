<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRole;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowRoleController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowRole::query()->with(['role']);

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_role', 'create'));
        $workflowRole = WorkflowRole::create($data);
        return response()->json($workflowRole, 201);
    }

    public function show($id)
    {
        return WorkflowRole::with(['role'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowRole = WorkflowRole::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_role', 'update', $workflowRole));
        $workflowRole->update($data);
        return $workflowRole;
    }

    public function destroy($id)
    {
        $workflowRole = WorkflowRole::findOrFail($id);
        $workflowRole->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
