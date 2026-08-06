<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowActionLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowActionLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowActionLog::query()->with(['workflowInstance', 'actionBy']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_action_log', 'create'));
        $workflowActionLog = WorkflowActionLog::create($data);
        return response()->json($workflowActionLog, 201);
    }

    public function show($id)
    {
        return WorkflowActionLog::with(['workflowInstance', 'actionBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowActionLog = WorkflowActionLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_action_log', 'update', $workflowActionLog));
        $workflowActionLog->update($data);
        return $workflowActionLog;
    }

    public function destroy($id)
    {
        $workflowActionLog = WorkflowActionLog::findOrFail($id);
        $workflowActionLog->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
