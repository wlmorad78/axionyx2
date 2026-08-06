<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTemplate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowTemplate::query()->with(['templateSteps']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_name', 'like', "%{$s}%")
                    ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_template', 'create'));
        $workflowTemplate = WorkflowTemplate::create($data);
        return response()->json($workflowTemplate, 201);
    }

    public function show($id)
    {
        return WorkflowTemplate::with(['templateSteps'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowTemplate = WorkflowTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_template', 'update', $workflowTemplate));
        $workflowTemplate->update($data);
        return $workflowTemplate;
    }

    public function destroy($id)
    {
        $workflowTemplate = WorkflowTemplate::findOrFail($id);
        $workflowTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
