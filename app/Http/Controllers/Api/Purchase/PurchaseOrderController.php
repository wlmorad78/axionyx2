<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'quotation', 'createdByEmployee', 'approvedByEmployee']);

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
            $query->where('po_no', 'like', '%' . $request->search . '%');
        }

        $purchaseOrders = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($purchaseOrders);
    }

    public function store(Request $request)
    {
        $id = null;
        $isUpdate = false;
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'po_no' => ['nullable', 'string', 'max:50', Rule::unique('purchase_orders', 'po_no')],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'quotation_id' => ['nullable', 'exists:supplier_quotations,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'discount_total' => ['sometimes', 'numeric', 'min:0'],
            'tax_total' => ['sometimes', 'numeric', 'min:0'],
            'net_total' => ['sometimes', 'numeric'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:20', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'closed', 'partially_received', 'received'])],
            'created_by' => ['nullable', 'exists:employees,id'],
            'approved_by' => ['nullable', 'exists:employees,id'],
        ]);

        $purchaseOrder = PurchaseOrder::create($validated);

        return response()->json($purchaseOrder, 201);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'quotation', 'items.item', 'createdByEmployee', 'approvedByEmployee']);

        return response()->json($purchaseOrder);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $id = $purchaseOrder->id;
        $isUpdate = true;
        $validated = $request->validate([
            'company_id' => ['sometimes', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'po_no' => ['sometimes', 'string', 'max:50', Rule::unique('purchase_orders', 'po_no')->ignore($id)],
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'quotation_id' => ['nullable', 'exists:supplier_quotations,id'],
            'order_date' => ['sometimes', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'discount_total' => ['sometimes', 'numeric', 'min:0'],
            'tax_total' => ['sometimes', 'numeric', 'min:0'],
            'net_total' => ['sometimes', 'numeric'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:20', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'closed', 'partially_received', 'received'])],
            'created_by' => ['nullable', 'exists:employees,id'],
            'approved_by' => ['nullable', 'exists:employees,id'],
        ]);

        $purchaseOrder->update($validated);

        return response()->json($purchaseOrder);
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();

        return response()->json(['message' => 'Purchase order deleted successfully']);
    }

    public function restore($id)
    {
        $purchaseOrder = PurchaseOrder::withTrashed()->findOrFail($id);
        $purchaseOrder->restore();

        return response()->json(['message' => 'Purchase order restored successfully']);
    }

    public function forceDelete($id)
    {
        $purchaseOrder = PurchaseOrder::withTrashed()->findOrFail($id);
        $purchaseOrder->forceDelete();

        return response()->json(['message' => 'Purchase order permanently deleted']);
    }

    public function schema()
    {
        return response()->json([
            'columns' => [
                'id' => 'bigint',
                'company_id' => 'bigint',
                'branch_id' => 'bigint',
                'po_no' => 'string',
                'supplier_id' => 'bigint',
                'quotation_id' => 'bigint',
                'order_date' => 'date',
                'expected_delivery_date' => 'date',
                'subtotal' => 'decimal',
                'discount_total' => 'decimal',
                'tax_total' => 'decimal',
                'net_total' => 'decimal',
                'notes' => 'text',
                'status' => 'string',
                'created_by' => 'bigint',
                'approved_by' => 'bigint',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
                'deleted_at' => 'timestamp',
            ],
        ]);
    }
}
