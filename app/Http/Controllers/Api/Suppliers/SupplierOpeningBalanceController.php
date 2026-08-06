<?php

namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Suppliers\SupplierOpeningBalance;
use Illuminate\Http\Request;

class SupplierOpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierOpeningBalance::with(['supplier', 'branch']);
        $companyId = $request->company_id ?? $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if ($companyId) $query->where('company_id', $companyId);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('supplier', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('supplier_code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'balance_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!isset($data['company_id'])) {
            $data['company_id'] = $request->user()->company_id ?? auth()->user()->company_id;
        }
        if (!isset($data['created_by'])) {
            $data['created_by'] = $request->user()->id ?? auth()->id();
        }
        if (!isset($data['balance'])) {
            $data['balance'] = ($data['credit'] ?? 0) - ($data['debit'] ?? 0);
        }

        $record = SupplierOpeningBalance::create($data);

        $supplier = $record->supplier;
        if ($supplier) {
            $totalCredit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('credit');
            $totalDebit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('debit');
            $supplier->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json($record->load(['supplier', 'branch']), 201);
    }

    public function show(SupplierOpeningBalance $supplierOpeningBalance)
    {
        return $supplierOpeningBalance->load(['supplier', 'branch', 'createdBy']);
    }

    public function update(Request $request, SupplierOpeningBalance $supplierOpeningBalance)
    {
        $data = $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'balance_date' => 'sometimes|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (isset($data['credit']) || isset($data['debit'])) {
            $debit = $data['debit'] ?? $supplierOpeningBalance->debit;
            $credit = $data['credit'] ?? $supplierOpeningBalance->credit;
            $data['balance'] = $credit - $debit;
        }

        $supplierOpeningBalance->update($data);

        $supplier = $supplierOpeningBalance->supplier;
        if ($supplier) {
            $totalCredit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('credit');
            $totalDebit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('debit');
            $supplier->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json($supplierOpeningBalance->load(['supplier', 'branch']));
    }

    public function destroy(SupplierOpeningBalance $supplierOpeningBalance)
    {
        $supplier = $supplierOpeningBalance->supplier;
        $supplierOpeningBalance->delete();

        if ($supplier) {
            $totalCredit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('credit');
            $totalDebit = SupplierOpeningBalance::where('supplier_id', $supplier->id)->sum('debit');
            $supplier->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SupplierOpeningBalance::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }
}
