<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowSlaRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowSlaRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowSlaRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_sla_rule', 'create'));
        $workflowSlaRule = WorkflowSlaRule::create($data);
        return response()->json($workflowSlaRule, 201);
    }

    public function show($id)
    {
        return WorkflowSlaRule::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowSlaRule = WorkflowSlaRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_sla_rule', 'update', $workflowSlaRule));
        $workflowSlaRule->update($data);
        return $workflowSlaRule;
    }

    public function destroy($id)
    {
        $workflowSlaRule = WorkflowSlaRule::findOrFail($id);
        $workflowSlaRule->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
