<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDefinition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowDefinitionController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowDefinition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_code', 'like', "%{$s}%")
                    ->orWhere('workflow_name', 'like', "%{$s}%")
                    ->orWhere('module_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_definition', 'create'));
        $workflowDefinition = WorkflowDefinition::create($data);
        return response()->json($workflowDefinition, 201);
    }

    public function show($id)
    {
        return WorkflowDefinition::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowDefinition = WorkflowDefinition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_definition', 'update', $workflowDefinition));
        $workflowDefinition->update($data);
        return $workflowDefinition;
    }

    public function destroy($id)
    {
        $workflowDefinition = WorkflowDefinition::findOrFail($id);
        $workflowDefinition->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $workflowDefinition = WorkflowDefinition::withTrashed()->findOrFail($id);
        $workflowDefinition->restore();
        return $workflowDefinition;
    }

    public function forceDelete($id)
    {
        $workflowDefinition = WorkflowDefinition::withTrashed()->findOrFail($id);
        $workflowDefinition->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
