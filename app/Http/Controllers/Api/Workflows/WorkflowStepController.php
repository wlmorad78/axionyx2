<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('step_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);
        if ($request->filled('workflow_definition_id')) $query->where('workflow_definition_id', $request->workflow_definition_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_step', 'create'));
        $workflowStep = WorkflowStep::create($data);
        return response()->json($workflowStep, 201);
    }

    public function show($id)
    {
        return WorkflowStep::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowStep = WorkflowStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_step', 'update', $workflowStep));
        $workflowStep->update($data);
        return $workflowStep;
    }

    public function destroy($id)
    {
        $workflowStep = WorkflowStep::findOrFail($id);
        $workflowStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $workflowStep = WorkflowStep::withTrashed()->findOrFail($id);
        $workflowStep->restore();
        return $workflowStep;
    }

    public function forceDelete($id)
    {
        $workflowStep = WorkflowStep::withTrashed()->findOrFail($id);
        $workflowStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
