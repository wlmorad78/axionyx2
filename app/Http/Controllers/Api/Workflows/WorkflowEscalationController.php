<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowEscalation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowEscalationController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowEscalation::query()->with(['workflowStep', 'escalateToRole']);

        if ($request->filled('workflow_step_id')) $query->where('workflow_step_id', $request->workflow_step_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_escalation', 'create'));
        $workflowEscalation = WorkflowEscalation::create($data);
        return response()->json($workflowEscalation, 201);
    }

    public function show($id)
    {
        return WorkflowEscalation::with(['workflowStep', 'escalateToRole'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowEscalation = WorkflowEscalation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_escalation', 'update', $workflowEscalation));
        $workflowEscalation->update($data);
        return $workflowEscalation;
    }

    public function destroy($id)
    {
        $workflowEscalation = WorkflowEscalation::findOrFail($id);
        $workflowEscalation->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
