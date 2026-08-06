<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{EInvoiceProvider};
use App\Support\ValidationRules;

class EInvoiceProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = EInvoiceProvider::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('provider_name', 'like', "%{$s}%")
                  ->orWhere('provider_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('e_invoice_provider', 'create'));
        $eInvoiceProvider = EInvoiceProvider::create($data);
        return response()->json($eInvoiceProvider, 201);
    }

    public function show($id)
    {
        return EInvoiceProvider::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $eInvoiceProvider = EInvoiceProvider::findOrFail($id);
        $data = $request->validate(ValidationRules::for('e_invoice_provider', 'update', $eInvoiceProvider));
        $eInvoiceProvider->update($data);
        return $eInvoiceProvider;
    }

    public function destroy($id)
    {
        $eInvoiceProvider = EInvoiceProvider::findOrFail($id);
        $eInvoiceProvider->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $eInvoiceProvider = EInvoiceProvider::withTrashed()->findOrFail($id);
        $eInvoiceProvider->restore();
        return $eInvoiceProvider;
    }

    public function forceDelete($id)
    {
        $eInvoiceProvider = EInvoiceProvider::withTrashed()->findOrFail($id);
        $eInvoiceProvider->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
