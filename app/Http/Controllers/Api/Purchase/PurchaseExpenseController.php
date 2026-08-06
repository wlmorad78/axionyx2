<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseExpense;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseExpense::with(['purchaseInvoice']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('purchase_invoice_id')) {
            $query->where('purchase_invoice_id', $request->purchase_invoice_id);
        }
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }
        if ($request->filled('search')) {
            $query->where('expense_no', 'like', '%' . $request->search . '%');
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_expense', 'store'));
        $expense = PurchaseExpense::create($validated);

        return response()->json($expense, 201);
    }

    public function show(PurchaseExpense $purchaseExpense)
    {
        $purchaseExpense->load(['purchaseInvoice', 'company']);

        return response()->json($purchaseExpense);
    }

    public function update(Request $request, PurchaseExpense $purchaseExpense)
    {
        $validated = $request->validate(ValidationRules::for('purchase_expense', 'update', $purchaseExpense));
        $purchaseExpense->update($validated);

        return response()->json($purchaseExpense);
    }

    public function destroy(PurchaseExpense $purchaseExpense)
    {
        $purchaseExpense->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PurchaseExpense::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PurchaseExpense::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_expense', 'store');
    }
}
