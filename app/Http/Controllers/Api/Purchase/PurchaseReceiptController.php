<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReceipt;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReceipt::with(['supplier', 'warehouse', 'createdByEmployee']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('receipt_no', 'like', '%' . $request->search . '%');
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt', 'store'));
        $receipt = PurchaseReceipt::create($validated);

        return response()->json($receipt, 201);
    }

    public function show(PurchaseReceipt $purchaseReceipt)
    {
        $purchaseReceipt->load(['supplier', 'purchaseOrder', 'items.item', 'items.unit', 'warehouse', 'createdByEmployee']);

        return response()->json($purchaseReceipt);
    }

    public function update(Request $request, PurchaseReceipt $purchaseReceipt)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt', 'update', $purchaseReceipt));
        $purchaseReceipt->update($validated);

        return response()->json($purchaseReceipt);
    }

    public function destroy(PurchaseReceipt $purchaseReceipt)
    {
        $purchaseReceipt->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = PurchaseReceipt::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        PurchaseReceipt::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_receipt', 'store');
    }
}
