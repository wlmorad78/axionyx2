<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{BudgetLine};
use App\Support\ValidationRules;

class BudgetLineController extends Controller
{
    public function index(Request $request)
    {
        $query = BudgetLine::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('planned_amount', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('budget_line', 'create'));
        $budgetLine = BudgetLine::create($data);
        return response()->json($budgetLine, 201);
    }

    public function show($id)
    {
        return BudgetLine::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $budgetLine = BudgetLine::findOrFail($id);
        $data = $request->validate(ValidationRules::for('budget_line', 'update', $budgetLine));
        $budgetLine->update($data);
        return $budgetLine;
    }

    public function destroy($id)
    {
        $budgetLine = BudgetLine::findOrFail($id);
        $budgetLine->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $budgetLine = BudgetLine::withTrashed()->findOrFail($id);
        $budgetLine->restore();
        return $budgetLine;
    }

    public function forceDelete($id)
    {
        $budgetLine = BudgetLine::withTrashed()->findOrFail($id);
        $budgetLine->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
