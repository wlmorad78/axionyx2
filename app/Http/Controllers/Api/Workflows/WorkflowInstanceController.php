<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowInstance::query()->with(['workflow', 'startedBy']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('instance_no', 'like', "%{$s}%")
                    ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_instance', 'create'));

        if (empty($data['instance_no'])) {
            $lastInstance = WorkflowInstance::withTrashed()->orderByDesc('id')->first();
            $nextNumber = $lastInstance ? (int) substr($lastInstance->instance_no, 3) + 1 : 1;
            $data['instance_no'] = 'WI-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        $workflowInstance = WorkflowInstance::create($data);
        return response()->json($workflowInstance, 201);
    }

    public function show($id)
    {
        return WorkflowInstance::with(['workflow', 'startedBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowInstance = WorkflowInstance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_instance', 'update', $workflowInstance));
        $workflowInstance->update($data);
        return $workflowInstance;
    }

    public function destroy($id)
    {
        $workflowInstance = WorkflowInstance::findOrFail($id);
        $workflowInstance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $workflowInstance = WorkflowInstance::withTrashed()->findOrFail($id);
        $workflowInstance->restore();
        return $workflowInstance;
    }

    public function forceDelete($id)
    {
        $workflowInstance = WorkflowInstance::withTrashed()->findOrFail($id);
        $workflowInstance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
