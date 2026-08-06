<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTemplateStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTemplateStepController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowTemplateStep::query()->with(['role']);

        if ($request->filled('workflow_template_id')) $query->where('workflow_template_id', $request->workflow_template_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_template_step', 'create'));
        $workflowTemplateStep = WorkflowTemplateStep::create($data);
        return response()->json($workflowTemplateStep, 201);
    }

    public function show($id)
    {
        return WorkflowTemplateStep::with(['role'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowTemplateStep = WorkflowTemplateStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_template_step', 'update', $workflowTemplateStep));
        $workflowTemplateStep->update($data);
        return $workflowTemplateStep;
    }

    public function destroy($id)
    {
        $workflowTemplateStep = WorkflowTemplateStep::findOrFail($id);
        $workflowTemplateStep->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
