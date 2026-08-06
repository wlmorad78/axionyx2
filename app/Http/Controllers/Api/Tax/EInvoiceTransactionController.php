<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{EInvoiceTransaction};
use App\Support\ValidationRules;

class EInvoiceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = EInvoiceTransaction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('external_reference', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('e_invoice_transaction', 'create'));
        $eInvoiceTransaction = EInvoiceTransaction::create($data);
        return response()->json($eInvoiceTransaction, 201);
    }

    public function show($id)
    {
        return EInvoiceTransaction::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('e_invoice_transaction', 'update', $eInvoiceTransaction));
        $eInvoiceTransaction->update($data);
        return $eInvoiceTransaction;
    }

    public function destroy($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::findOrFail($id);
        $eInvoiceTransaction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::withTrashed()->findOrFail($id);
        $eInvoiceTransaction->restore();
        return $eInvoiceTransaction;
    }

    public function forceDelete($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::withTrashed()->findOrFail($id);
        $eInvoiceTransaction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
