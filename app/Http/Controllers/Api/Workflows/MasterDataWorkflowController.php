<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataWorkflow;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataWorkflowController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDataWorkflow::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_name', 'like', "%{$s}%")
                  ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_workflow', 'create'));
        $masterDataWorkflow = MasterDataWorkflow::create($data);
        return response()->json($masterDataWorkflow, 201);
    }

    public function show($id)
    {
        return MasterDataWorkflow::with('steps')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $masterDataWorkflow = MasterDataWorkflow::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_workflow', 'update', $masterDataWorkflow));
        $masterDataWorkflow->update($data);
        return $masterDataWorkflow;
    }

    public function destroy($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::findOrFail($id);
        $masterDataWorkflow->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::withTrashed()->findOrFail($id);
        $masterDataWorkflow->restore();
        return $masterDataWorkflow;
    }

    public function forceDelete($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::withTrashed()->findOrFail($id);
        $masterDataWorkflow->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
