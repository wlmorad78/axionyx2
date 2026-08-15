<?php
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Sales\LoadRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LoadRequestController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LoadRequest::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->load_type) $query->where('load_type', $request->load_type);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'employee_id' => 'required|exists:employees,id',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $unitService = app(\App\Services\UnitConversionService::class);

        $repEmployeeId = $data['employee_id'];
        $existingOpenOrder = LoadRequest::where('employee_id', $repEmployeeId)
            ->whereIn('status', ['draft', 'pending', 'approved', 'loading'])
            ->first();

        if ($existingOpenOrder) {
            return response()->json([
                'message' => "المندوب مينفعش يكون عنده اتنين أوامر تحميل مفتوحين - عندك أمر تحميل رقم {$existingOpenOrder->request_no} لسه مفتوح ({$existingOpenOrder->status}). لازم تغلق/تسلم الأمر الأول.",
            ], 422);
        }

        $loadRequest = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $user, $unitService) {
            $lr = LoadRequest::create([
                'company_id' => $user->company_id,
                'branch_id' => $data['branch_id'] ?? $user->branch_id,
                'warehouse_id' => $data['warehouse_id'],
                'employee_id' => $data['employee_id'],
                'request_date' => now()->toDateString(),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'requested_by' => $data['employee_id'],
            ]);

            foreach ($data['items'] as $item) {
                $itemId = $item['item_id'];
                $qty = (float) $item['quantity'];
                $price = (float) ($item['unit_price'] ?? 0);
                $unitId = $item['unit_id'] ?? null;

                $resolved = $unitService->resolveUnit($itemId, $unitId);
                $finalUnitId = $resolved?->unit_id ?? $unitId;
                $conversionFactor = $resolved?->conversion_factor ?? 1;
                $baseQuantity = $unitService->toBase($itemId, $finalUnitId, $qty);

                \App\Models\Sales\LoadRequestItem::create([
                    'load_request_id' => $lr->id,
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_id' => $finalUnitId,
                    'conversion_factor' => $conversionFactor,
                    'base_quantity' => $baseQuantity,
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                ]);
            }

            return $lr;
        });

        return response()->json($loadRequest->load('items.item'), 201);
    }

    public function show(LoadRequest $loadRequest)
    {
        $loadRequest->load([
            'company', 'branch', 'warehouse', 'employee', 'supervisorEmployee',
            'salesTerritory', 'requestedByEmployee', 'createByEmployee',
            'items.item', 'items.unit',
            'issueOrder.items.item',
        ]);

        $data = $loadRequest->toArray();

        $ioItems = $loadRequest->issueOrder?->items->keyBy('item_id');

        foreach ($data['items'] as $i => $item) {
            $ioItem = $ioItems?->get($item['item_id']);
            $data['items'][$i]['issued_quantity'] = $ioItem ? (float)$ioItem->issued_quantity : null;
        }

        return response()->json($data);
    }

    public function update(Request $request, LoadRequest $loadRequest)
    {
        $data = $request->validate(ValidationRules::for('load_request', 'update', $loadRequest));

        if ($loadRequest->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن تعديل طلب تحميل بحالة مختلفة عن قيد المراجعة'], 422);
        }

        $loadRequest->update($data);

        if ($request->has('items') && is_array($request->items)) {
            $unitService = app(\App\Services\UnitConversionService::class);

            $existingItemIds = $loadRequest->items->pluck('id')->toArray();
            $submittedItemIds = collect($request->items)->pluck('item_id')->toArray();

            $idsToDelete = array_diff($existingItemIds, array_map(function ($item) use ($loadRequest) {
                $existing = $loadRequest->items->firstWhere('item_id', $item['item_id'] ?? null);
                return $existing?->id;
            }, $request->items));

            \App\Models\Sales\LoadRequestItem::whereIn('id', $idsToDelete)->delete();

            foreach ($request->items as $itemData) {
                $existingItem = $loadRequest->items->firstWhere('item_id', $itemData['item_id']);

                $qty = (float) ($itemData['quantity'] ?? 1);
                $unitId = $itemData['unit_id'] ?? $existingItem?->unit_id;
                $unitPrice = (float) ($itemData['unit_price'] ?? $existingItem?->unit_price ?? 0);
                $baseQty = $unitId ? $unitService->toBase($itemData['item_id'], $unitId, $qty) : $qty;

                if ($existingItem) {
                    $existingItem->update([
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $qty * $unitPrice,
                    ]);
                } else {
                    \App\Models\Sales\LoadRequestItem::create([
                        'load_request_id' => $loadRequest->id,
                        'item_id' => $itemData['item_id'],
                        'unit_id' => $unitId,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $unitPrice,
                        'total_price' => $qty * $unitPrice,
                    ]);
                }
            }

            $loadRequest->refresh()->load('items');
            $totalQty = (float) $loadRequest->items->sum('quantity');
            $totalAmount = (float) $loadRequest->items->sum('total_price');
            $totalItems = $loadRequest->items->count();
            $loadRequest->update([
                'total_quantity' => $totalQty,
                'total_amount' => $totalAmount,
                'total_items_count' => $totalItems,
            ]);
        }

        return response()->json($loadRequest->load(['items.item', 'employee', 'warehouse']));
    }

    public function destroy(LoadRequest $loadRequest)
    {
        $loadRequest->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = LoadRequest::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        LoadRequest::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('load_request', 'store');
    }

    public function updateStatus(Request $request, LoadRequest $loadRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,pending,approved,rejected,loading,completed,cancelled,closed',
        ]);
        $loadRequest->update($data);
        return response()->json($loadRequest);
    }

    public function approve(Request $request, LoadRequest $loadRequest)
    {
        $employee = \App\Models\HR\Employee::where('email', $request->user()->email)->first();

        $unitService = app(\App\Services\UnitConversionService::class);
        $itemsData = $request->input('items', []);

        foreach ($loadRequest->items as $loadItem) {
            if (isset($itemsData[$loadItem->id]['quantity'])) {
                $newQty = (float) $itemsData[$loadItem->id]['quantity'];
                $newBaseQty = $unitService->toBase($loadItem->item_id, $loadItem->unit_id, $newQty);
                $newTotalPrice = $newQty * $loadItem->unit_price;

                $loadItem->update([
                    'quantity' => $newQty,
                    'base_quantity' => $newBaseQty,
                    'total_price' => $newTotalPrice,
                ]);
            }
        }

        $loadRequest->refresh()->load('items');
        $totalQty = (float) $loadRequest->items->sum('quantity');
        $totalAmount = (float) $loadRequest->items->sum('total_price');
        $loadRequest->update([
            'total_quantity' => $totalQty,
            'total_amount' => $totalAmount,
        ]);

        $loadRequest->load('items.item');
        $warehouseId = $loadRequest->warehouse_id;
        $errors = [];

        // Bulk fetch stock for all items (avoid N+1)
        $itemIds = $loadRequest->items->pluck('item_id')->unique()->values()->toArray();
        $stockMap = $this->getBulkWarehouseStock($warehouseId, $itemIds);

        foreach ($loadRequest->items as $loadItem) {
            $available = $stockMap->get($loadItem->item_id, 0);
            if ($available < $loadItem->base_quantity) {
                $itemName = $loadItem->item?->name_ar ?? "صنف #{$loadItem->item_id}";
                $errors[] = "{$itemName}: المتاح {$available}، المطلوب {$loadItem->base_quantity}";
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => "الكميات غير كافية في المخزن:\n" . implode("\n", $errors),
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($loadRequest, $request, $employee) {
            $loadRequest->update([
                'status' => 'approved',
                'supervisor_employee_id' => $employee?->id,
                'create_notes' => $request->input('notes', ''),
            ]);

            $issueOrder = \App\Models\Inventory\IssueOrder::create([
                'company_id' => $loadRequest->company_id,
                'warehouse_id' => $loadRequest->warehouse_id,
                'load_request_id' => $loadRequest->id,
                'issue_date' => now()->toDateString(),
                'issue_time' => now()->toTimeString(),
                'employee_id' => $loadRequest->employee_id,
                'sales_territory_id' => $loadRequest->sales_territory_id,
                'status' => 'issued',
                'issued_by' => $employee?->id,
                'approved_by' => $employee?->id,
                'approved_at' => now(),
                'notes' => "ØµØ§Ø¯Ø± Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø£Ù…Ø± Ø§Ù„ØªØ­Ù…ÙŠÙ„ {$loadRequest->request_no}",
            ]);

            foreach ($loadRequest->items as $loadItem) {
                $baseQty = (float) ($loadItem->base_quantity ?? 0);
                $cf = (float) ($loadItem->conversion_factor ?? 1);
                $baseUnitId = $loadItem->unit_id;

                if ($baseQty <= 0 && $cf > 0) {
                    $baseQty = (float) $loadItem->quantity * $cf;
                }

                \App\Models\Inventory\IssueOrderItem::create([
                    'issue_order_id' => $issueOrder->id,
                    'item_id' => $loadItem->item_id,
                    'unit_id' => $baseUnitId,
                    'conversion_factor' => $cf,
                    'base_quantity' => $baseQty,
                    'requested_quantity' => $loadItem->quantity,
                    'issued_quantity' => $loadItem->quantity,
                    'purchase_price' => $loadItem->unit_price,
                    'sales_price' => $loadItem->unit_price,
                    'total_amount' => $loadItem->total_price,
                ]);
            }

            $type = \App\Models\Inventory\InventoryTransactionType::where('code', 'ISSUE_ORDER')->first();
            if (!$type) {
                $type = \App\Models\Inventory\InventoryTransactionType::firstOrCreate(
                    ['code' => 'ISSUE_ORDER'],
                    ['name' => 'Ø£Ù…Ø± ØµØ±Ù', 'effect' => 'subtraction', 'is_active' => true]
                );
            }

            $txn = \App\Models\Inventory\InventoryTransaction::create([
                'company_id' => $loadRequest->company_id,
                'warehouse_id' => $loadRequest->warehouse_id,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\Inventory\InventoryTransaction::nextTransactionNo($loadRequest->company_id),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => \App\Models\Inventory\IssueOrder::class,
                'reference_id' => $issueOrder->id,
                'notes' => "Ø¥Ø°Ù† ØµØ±Ù Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø£Ù…Ø± Ø§Ù„ØªØ­Ù…ÙŠÙ„ {$loadRequest->request_no}",
                'status' => 'posted',
                'created_by' => $employee?->id,
            ]);

            foreach ($loadRequest->items as $loadItem) {
                $itemId = $loadItem->item_id;
                $baseQty = (float) ($loadItem->base_quantity ?? 0);
                $cf = (float) ($loadItem->conversion_factor ?? 1);
                $unitId = $loadItem->unit_id;

                if ($baseQty <= 0 && $cf > 0) {
                    $baseQty = (float) $loadItem->quantity * $cf;
                }

                $unitService = app(\App\Services\UnitConversionService::class);
                $baseUnitId = $unitService->getBaseUnitId($itemId) ?? $unitId;

                \App\Models\Inventory\InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $itemId,
                    'unit_id' => $baseUnitId,
                    'conversion_factor' => $cf,
                    'qty' => -$baseQty,
                    'unit_cost' => $loadItem->unit_price,
                    'total_cost' => $loadItem->total_price,
                    'from_location_type' => 'warehouse',
                    'from_location_id' => $loadRequest->warehouse_id,
                    'to_location_type' => 'rep',
                    'to_location_id' => $loadRequest->employee_id,
                ]);
            }

            $loadRequest->update(['status' => 'loading']);
        });

        return response()->json([
            'message' => 'ØªÙ…Øª Ø§Ù„Ù…ÙˆØ§ÙÙ‚Ø© Ø¹Ù„Ù‰ Ø·Ù„Ø¨ Ø§Ù„ØªØ­Ù…ÙŠÙ„ ÙˆØ¥Ù†Ø´Ø§Ø¡ Ø¥Ø°Ù† Ø§Ù„ØµØ±Ù Ø¨Ù†Ø¬Ø§Ø­',
            'data' => $loadRequest->fresh(),
        ]);
    }

    public function reject(Request $request, LoadRequest $loadRequest)
    {
        $loadRequest->update([
            'status' => 'cancelled',
            'create_notes' => $request->input('notes', 'Ù…Ø±ÙÙˆØ¶'),
        ]);

        return response()->json([
            'message' => 'ØªÙ… Ø±ÙØ¶ Ø·Ù„Ø¨ Ø§Ù„ØªØ­Ù…ÙŠÙ„',
            'data' => $loadRequest->fresh(),
        ]);
    }

    protected function getWarehouseStock(int $warehouseId, int $itemId): float
    {
        $txnQty = \App\Models\Inventory\InventoryTransactionItem::query()
            ->selectRaw('COALESCE(SUM(inventory_transaction_items.qty), 0) as total')
            ->join('inventory_transactions', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->where('inventory_transaction_items.item_id', $itemId)
            ->where('inventory_transactions.warehouse_id', $warehouseId)
            ->where('inventory_transactions.status', 'posted')
            ->whereNull('inventory_transactions.deleted_at')
            ->value('total');

        $unitService = app(\App\Services\UnitConversionService::class);

        $obRecords = \App\Models\Inventory\InventoryOpeningBalance::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->get();

        $obQty = 0;
        foreach ($obRecords as $ob) {
            $conversionFactor = $unitService->getConversionFactor($itemId, $ob->unit_id);
            $obQty += (float)$ob->qty * $conversionFactor;
        }

        return (float) $txnQty + (float) $obQty;
    }

    protected function getBulkWarehouseStock(int $warehouseId, array $itemIds): \Illuminate\Support\Collection
    {
        if (empty($itemIds)) {
            return collect();
        }

        $txnQtys = \App\Models\Inventory\InventoryTransactionItem::query()
            ->selectRaw('item_id, COALESCE(SUM(inventory_transaction_items.qty), 0) as total')
            ->join('inventory_transactions', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->whereIn('inventory_transaction_items.item_id', $itemIds)
            ->where('inventory_transactions.warehouse_id', $warehouseId)
            ->where('inventory_transactions.status', 'posted')
            ->whereNull('inventory_transactions.deleted_at')
            ->groupBy('item_id')
            ->pluck('total', 'item_id');

        $obRecords = \App\Models\Inventory\InventoryOpeningBalance::query()
            ->whereIn('item_id', $itemIds)
            ->where('warehouse_id', $warehouseId)
            ->get();

        $unitService = app(\App\Services\UnitConversionService::class);

        $obQtys = collect();
        foreach ($obRecords as $ob) {
            $conversionFactor = $unitService->getConversionFactor($ob->item_id, $ob->unit_id);
            $current = (float) ($obQtys->get($ob->item_id) ?? 0);
            $obQtys->put($ob->item_id, $current + (float)$ob->qty * $conversionFactor);
        }

        $stockMap = collect();
        foreach ($itemIds as $itemId) {
            $txn = (float) ($txnQtys->get($itemId) ?? 0);
            $ob = (float) ($obQtys->get($itemId) ?? 0);
            $stockMap->put($itemId, $txn + $ob);
        }

        return $stockMap;
    }
}
