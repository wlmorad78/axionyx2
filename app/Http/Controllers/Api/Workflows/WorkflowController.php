<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\Workflow;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        $query = Workflow::query()->with(['workflowType']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('workflow_type_id')) $query->where('workflow_type_id', $request->workflow_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow', 'create'));
        $workflow = Workflow::create($data);
        return response()->json($workflow, 201);
    }

    public function show($id)
    {
        return Workflow::with(['workflowType'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflow = Workflow::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow', 'update', $workflow));
        $workflow->update($data);
        return $workflow;
    }

    public function destroy($id)
    {
        $workflow = Workflow::findOrFail($id);
        $workflow->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $workflow = Workflow::withTrashed()->findOrFail($id);
        $workflow->restore();
        return $workflow;
    }

    public function forceDelete($id)
    {
        $workflow = Workflow::withTrashed()->findOrFail($id);
        $workflow->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
