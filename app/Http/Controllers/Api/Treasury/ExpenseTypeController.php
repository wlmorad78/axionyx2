<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\ExpenseType;
use Illuminate\Http\Request;

class ExpenseTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseType::with('branch');

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'code' => ['required', 'string', 'max:50', 'unique:expense_types,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(ExpenseType::create($data), 201);
    }

    public function show(ExpenseType $expenseType)
    {
        return $expenseType;
    }

    public function update(Request $request, ExpenseType $expenseType)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:expense_types,code,' . $expenseType->id],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $expenseType->update($data);
        return response()->json($expenseType);
    }

    public function destroy(ExpenseType $expenseType)
    {
        $expenseType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = ExpenseType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        ExpenseType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = ExpenseType::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'EX-%')
            ->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^EX-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        }
        return response()->json(['next_code' => 'EX-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT)]);
    }
}
