<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowCondition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowConditionController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowCondition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('field_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_condition', 'create'));
        $workflowCondition = WorkflowCondition::create($data);
        return response()->json($workflowCondition, 201);
    }

    public function show($id)
    {
        return WorkflowCondition::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowCondition = WorkflowCondition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_condition', 'update', $workflowCondition));
        $workflowCondition->update($data);
        return $workflowCondition;
    }

    public function destroy($id)
    {
        $workflowCondition = WorkflowCondition::findOrFail($id);
        $workflowCondition->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
