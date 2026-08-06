<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_code', 'like', "%{$s}%")
                    ->orWhere('workflow_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_type', 'create'));
        $workflowType = WorkflowType::create($data);
        return response()->json($workflowType, 201);
    }

    public function show($id)
    {
        return WorkflowType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowType = WorkflowType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_type', 'update', $workflowType));
        $workflowType->update($data);
        return $workflowType;
    }

    public function destroy($id)
    {
        $workflowType = WorkflowType::findOrFail($id);
        $workflowType->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $workflowType = WorkflowType::withTrashed()->findOrFail($id);
        $workflowType->restore();
        return $workflowType;
    }

    public function forceDelete($id)
    {
        $workflowType = WorkflowType::withTrashed()->findOrFail($id);
        $workflowType->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
