<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\OpportunityStage;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpportunityStageController extends Controller
{
    public function index(Request $request)
    {
        $query = OpportunityStage::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opportunity_stage', 'create'));
        $opportunityStage = OpportunityStage::create($data);
        return response()->json($opportunityStage, 201);
    }

    public function show($id)
    {
        return OpportunityStage::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $opportunityStage = OpportunityStage::findOrFail($id);
        $data = $request->validate(ValidationRules::for('opportunity_stage', 'update', $opportunityStage));
        $opportunityStage->update($data);
        return $opportunityStage;
    }

    public function destroy($id)
    {
        $opportunityStage = OpportunityStage::findOrFail($id);
        $opportunityStage->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $opportunityStage = OpportunityStage::withTrashed()->findOrFail($id);
        $opportunityStage->restore();
        return $opportunityStage;
    }

    public function forceDelete($id)
    {
        $opportunityStage = OpportunityStage::withTrashed()->findOrFail($id);
        $opportunityStage->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
