<?php
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierLedger;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierLedger::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_ledger', 'store'));
        return response()->json(SupplierLedger::create($data), 201);
    }

    public function show(SupplierLedger $supplierLedger)
    {
        return $supplierLedger->load(['supplier', 'account', 'company', 'branch', 'createdByEmployee']);
    }

    public function update(Request $request, SupplierLedger $supplierLedger)
    {
        $data = $request->validate(ValidationRules::for('supplier_ledger', 'update', $supplierLedger));
        $supplierLedger->update($data);
        return response()->json($supplierLedger);
    }

    public function destroy(SupplierLedger $supplierLedger)
    {
        $supplierLedger->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SupplierLedger::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SupplierLedger::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('supplier_ledger', 'store');
    }
}
