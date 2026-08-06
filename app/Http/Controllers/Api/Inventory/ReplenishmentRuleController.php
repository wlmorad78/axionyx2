<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ReplenishmentRule};
use App\Support\ValidationRules;

class ReplenishmentRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = ReplenishmentRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('lead_time_days', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('replenishment_rule', 'create'));
        $replenishmentRule = ReplenishmentRule::create($data);
        return response()->json($replenishmentRule, 201);
    }

    public function show($id)
    {
        return ReplenishmentRule::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $replenishmentRule = ReplenishmentRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('replenishment_rule', 'update', $replenishmentRule));
        $replenishmentRule->update($data);
        return $replenishmentRule;
    }

    public function destroy($id)
    {
        $replenishmentRule = ReplenishmentRule::findOrFail($id);
        $replenishmentRule->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $replenishmentRule = ReplenishmentRule::withTrashed()->findOrFail($id);
        $replenishmentRule->restore();
        return $replenishmentRule;
    }

    public function forceDelete($id)
    {
        $replenishmentRule = ReplenishmentRule::withTrashed()->findOrFail($id);
        $replenishmentRule->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
