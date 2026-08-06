<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequestStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestStepController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterDataRequestStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('master_data_request_id')) $query->where('master_data_request_id', $request->master_data_request_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request_step', 'create'));
        $masterDataRequestStep = MasterDataRequestStep::create($data);
        return response()->json($masterDataRequestStep, 201);
    }

    public function show($id)
    {
        return MasterDataRequestStep::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $masterDataRequestStep = MasterDataRequestStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request_step', 'update', $masterDataRequestStep));
        $masterDataRequestStep->update($data);
        return $masterDataRequestStep;
    }

    public function destroy($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::findOrFail($id);
        $masterDataRequestStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::withTrashed()->findOrFail($id);
        $masterDataRequestStep->restore();
        return $masterDataRequestStep;
    }

    public function forceDelete($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::withTrashed()->findOrFail($id);
        $masterDataRequestStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
