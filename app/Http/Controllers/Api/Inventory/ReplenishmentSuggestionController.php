<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ReplenishmentSuggestion};
use App\Support\ValidationRules;

class ReplenishmentSuggestionController extends Controller
{
    public function index(Request $request)
    {
        $query = ReplenishmentSuggestion::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%")
                  ->orWhere('suggested_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('replenishment_suggestion', 'create'));
        $replenishmentSuggestion = ReplenishmentSuggestion::create($data);
        return response()->json($replenishmentSuggestion, 201);
    }

    public function show($id)
    {
        return ReplenishmentSuggestion::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::findOrFail($id);
        $data = $request->validate(ValidationRules::for('replenishment_suggestion', 'update', $replenishmentSuggestion));
        $replenishmentSuggestion->update($data);
        return $replenishmentSuggestion;
    }

    public function destroy($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::findOrFail($id);
        $replenishmentSuggestion->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::withTrashed()->findOrFail($id);
        $replenishmentSuggestion->restore();
        return $replenishmentSuggestion;
    }

    public function forceDelete($id)
    {
        $replenishmentSuggestion = ReplenishmentSuggestion::withTrashed()->findOrFail($id);
        $replenishmentSuggestion->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
