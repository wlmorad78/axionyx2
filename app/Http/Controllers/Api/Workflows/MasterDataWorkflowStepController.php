<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataWorkflowStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataWorkflowStepController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDataWorkflowStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('step_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('master_data_workflow_id')) $query->where('master_data_workflow_id', $request->master_data_workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_workflow_step', 'create'));
        $masterDataWorkflowStep = MasterDataWorkflowStep::create($data);
        return response()->json($masterDataWorkflowStep, 201);
    }

    public function show($id)
    {
        return MasterDataWorkflowStep::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_workflow_step', 'update', $masterDataWorkflowStep));
        $masterDataWorkflowStep->update($data);
        return $masterDataWorkflowStep;
    }

    public function destroy($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::findOrFail($id);
        $masterDataWorkflowStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::withTrashed()->findOrFail($id);
        $masterDataWorkflowStep->restore();
        return $masterDataWorkflowStep;
    }

    public function forceDelete($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::withTrashed()->findOrFail($id);
        $masterDataWorkflowStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
