<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Budget};
use App\Support\ValidationRules;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('budget_code', 'like', "%{$s}%")
                  ->orWhere('budget_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('budget', 'create'));
        $budget = Budget::create($data);
        return response()->json($budget, 201);
    }

    public function show($id)
    {
        return Budget::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::findOrFail($id);
        $data = $request->validate(ValidationRules::for('budget', 'update', $budget));
        $budget->update($data);
        return $budget;
    }

    public function destroy($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $budget = Budget::withTrashed()->findOrFail($id);
        $budget->restore();
        return $budget;
    }

    public function forceDelete($id)
    {
        $budget = Budget::withTrashed()->findOrFail($id);
        $budget->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
