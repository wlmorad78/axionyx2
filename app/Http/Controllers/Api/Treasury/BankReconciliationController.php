<?php
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = BankReconciliation::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('bank_reconciliation', 'store'));
        return response()->json(BankReconciliation::create($data), 201);
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        return $bankReconciliation->load(['bankAccount', 'company', 'branch', 'createdByEmployee']);
    }

    public function update(Request $request, BankReconciliation $bankReconciliation)
    {
        $data = $request->validate(ValidationRules::for('bank_reconciliation', 'update', $bankReconciliation));
        $bankReconciliation->update($data);
        return response()->json($bankReconciliation);
    }

    public function destroy(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = BankReconciliation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        BankReconciliation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('bank_reconciliation', 'store');
    }
}
