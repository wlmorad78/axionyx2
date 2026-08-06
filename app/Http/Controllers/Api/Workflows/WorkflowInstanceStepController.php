<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstanceStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowInstanceStepController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowInstanceStep::query()->with(['workflowStep', 'assignedTo']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_instance_step', 'create'));
        $workflowInstanceStep = WorkflowInstanceStep::create($data);
        return response()->json($workflowInstanceStep, 201);
    }

    public function show($id)
    {
        return WorkflowInstanceStep::with(['workflowStep', 'assignedTo'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowInstanceStep = WorkflowInstanceStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_instance_step', 'update', $workflowInstanceStep));
        $workflowInstanceStep->update($data);
        return $workflowInstanceStep;
    }

    public function destroy($id)
    {
        $workflowInstanceStep = WorkflowInstanceStep::findOrFail($id);
        $workflowInstanceStep->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
