<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\Expense;
use App\Models\Treasury\TreasuryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['expenseType', 'treasury']);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->expense_type_id) {
            $query->where('expense_type_id', $request->expense_type_id);
        }
        if ($request->date_from) {
            $query->where('expense_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%$s%")
                    ->orWhere('payee_name', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'expense_type_id' => ['nullable', 'exists:expense_types,id'],
            'code' => ['required', 'string', 'max:50'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'payment_method' => ['sometimes', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense = Expense::create($data);

        if (!empty($data['treasury_id']) && $data['amount'] > 0) {
            TreasuryTransaction::create([
                'company_id' => $data['company_id'],
                'treasury_id' => $data['treasury_id'],
                'type' => 'expense',
                'amount' => $data['amount'],
                'reference_type' => 'expense',
                'reference_id' => $expense->id,
                'description' => $data['description'] ?? 'مصروف',
                'transaction_date' => $data['expense_date'],
            ]);
        }

        return response()->json($expense, 201);
    }

    public function show(Expense $expense)
    {
        return $expense->load(['expenseType', 'treasury', 'branch']);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'treasury_id' => ['nullable', 'exists:treasuries,id'],
            'expense_type_id' => ['nullable', 'exists:expense_types,id'],
            'expense_date' => ['sometimes', 'date'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'payment_method' => ['sometimes', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense->update($data);
        return response()->json($expense);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = Expense::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        Expense::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = Expense::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'EXP-%')
            ->orderByRaw("CAST(SUBSTRING(code, 5) AS UNSIGNED) DESC")->first();
        $next = 1;
        if ($last && preg_match('/^EXP-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        }
        return response()->json(['next_code' => 'EXP-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT)]);
    }

    public function summary(Request $request)
    {
        $companyId = $request->company_id;
        $query = Expense::where('company_id', $companyId);

        if ($request->date_from) {
            $query->where('expense_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        $total = (clone $query)->sum('amount');
        $byType = (clone $query)
            ->join('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->select('expense_types.name_ar', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_types.name_ar')
            ->get();

        return response()->json([
            'total' => $total,
            'by_type' => $byType,
        ]);
    }
}
