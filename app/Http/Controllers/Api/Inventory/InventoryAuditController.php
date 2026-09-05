<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAudit;
use App\Models\InventoryAuditItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryAudit::with(['warehouse', 'createdBy', 'approvedBy']);

        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('audit_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'audit_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.system_qty' => 'required|numeric|min:0',
            'items.*.counted_qty' => 'required|numeric|min:0',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $warehouse = \App\Models\Warehouse::findOrFail($request->warehouse_id);

            $audit = InventoryAudit::create([
                'company_id' => $warehouse->company_id,
                'branch_id' => $warehouse->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'audit_date' => $request->audit_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $variance = (float) $item['counted_qty'] - (float) $item['system_qty'];
                $varianceCost = $variance * (float) $item['purchase_price'];

                InventoryAuditItem::create([
                    'inventory_audit_id' => $audit->id,
                    'item_id' => $item['item_id'],
                    'system_qty' => $item['system_qty'],
                    'counted_qty' => $item['counted_qty'],
                    'variance_qty' => $variance,
                    'purchase_price' => $item['purchase_price'],
                    'variance_cost' => $varianceCost,
                ]);
            }

            return response()->json(['success' => true, 'data' => $audit->load('items.item', 'warehouse')], 201);
        });
    }

    public function show(InventoryAudit $inventoryAudit)
    {
        return response()->json([
            'success' => true,
            'data' => $inventoryAudit->load([
                'warehouse', 'items.item', 'items.unit',
                'createdBy', 'approvedBy',
            ]),
        ]);
    }

    public function update(Request $request, InventoryAudit $inventoryAudit)
    {
        if ($inventoryAudit->status !== 'draft') {
            return response()->json(['message' => 'لا يمكن تعديل جرد معتمد'], 422);
        }

        $request->validate([
            'audit_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.system_qty' => 'required|numeric|min:0',
            'items.*.counted_qty' => 'required|numeric|min:0',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $inventoryAudit) {
            $inventoryAudit->update($request->only(['audit_date', 'notes']));

            if ($request->has('items')) {
                $inventoryAudit->items()->delete();

                foreach ($request->items as $item) {
                    $variance = (float) $item['counted_qty'] - (float) $item['system_qty'];
                    $varianceCost = $variance * (float) $item['purchase_price'];

                    InventoryAuditItem::create([
                        'inventory_audit_id' => $inventoryAudit->id,
                        'item_id' => $item['item_id'],
                        'system_qty' => $item['system_qty'],
                        'counted_qty' => $item['counted_qty'],
                        'variance_qty' => $variance,
                        'purchase_price' => $item['purchase_price'],
                        'variance_cost' => $varianceCost,
                    ]);
                }
            }

            return response()->json(['success' => true, 'data' => $inventoryAudit->load('items.item', 'warehouse')]);
        });
    }

    public function post(Request $request, InventoryAudit $inventoryAudit)
    {
        if ($inventoryAudit->status !== 'draft') {
            return response()->json(['message' => 'هذا الجرد معتمد بالفعل'], 422);
        }

        $inventoryAudit->load('items.item.itemUnits');

        return DB::transaction(function () use ($inventoryAudit) {
            $addTypeId = InventoryTransactionType::where('code', 'STOCK_ADJUSTMENT_ADD')->first()?->id;
            $subTypeId = InventoryTransactionType::where('code', 'STOCK_ADJUSTMENT_SUB')->first()?->id;

            $hasAdditions = $inventoryAudit->items->contains(fn($i) => $i->variance_qty > 0);
            $hasSubtractions = $inventoryAudit->items->contains(fn($i) => $i->variance_qty < 0);

            if (($hasAdditions && !$addTypeId) || ($hasSubtractions && !$subTypeId)) {
                throw new \RuntimeException('أنواع حركات تسوية المخزون غير مهيأة');
            }

            if ($hasAdditions) {
                $this->createTransaction($inventoryAudit, $addTypeId, $inventoryAudit->items->where('variance_qty', '>', 0), 'زيادة جرد');
            }

            if ($hasSubtractions) {
                $this->createTransaction($inventoryAudit, $subTypeId, $inventoryAudit->items->where('variance_qty', '<', 0), 'نقص جرد');
            }

            $inventoryAudit->update([
                'status' => 'posted',
                'approved_by' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'data' => $inventoryAudit->load('items.item', 'warehouse')]);
        });
    }

    protected function createTransaction(InventoryAudit $audit, int $typeId, $items, string $note): void
    {
        $last = InventoryTransaction::withTrashed()
            ->where('transaction_no', 'like', 'INV-%')
            ->orderByRaw("CAST(SUBSTR(transaction_no, 5) AS INTEGER) DESC")
            ->value('transaction_no');

        $next = 1;
        if ($last && preg_match('/^INV-(\d+)$/', $last, $m)) {
            $next = intval($m[1]) + 1;
        }
        $txnNo = 'INV-' . str_pad($next, 5, '0', STR_PAD_LEFT);

        $txn = InventoryTransaction::create([
            'company_id' => $audit->company_id,
            'branch_id' => $audit->branch_id,
            'transaction_type_id' => $typeId,
            'warehouse_id' => $audit->warehouse_id,
            'transaction_no' => $txnNo,
            'transaction_date' => $audit->audit_date,
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => InventoryAudit::class,
            'reference_id' => $audit->id,
            'notes' => "جرد مخزون #{$audit->audit_no} - $note",
            'status' => 'posted',
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
        ]);

        foreach ($items as $item) {
            $qty = abs($item->variance_qty);
            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id' => $item->item_id,
                'unit_id' => $item->unit_id ?: ($item->item?->base_unit_id ?: $item->item?->itemUnits
                    ?->sortByDesc('is_default')->sortByDesc('is_purchase_unit')->first()?->unit_id),
                'qty' => $item->variance_qty > 0 ? $qty : -$qty,
                'unit_cost' => $item->purchase_price,
                'total_cost' => abs($item->variance_cost),
            ]);
        }
    }

    public function destroy(InventoryAudit $inventoryAudit)
    {
        if ($inventoryAudit->status === 'posted') {
            return response()->json(['message' => 'لا يمكن حذف جرد معتمد'], 422);
        }
        $inventoryAudit->delete();
        return response()->json(null, 204);
    }
}
