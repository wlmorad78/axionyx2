<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LoadRequest;
use App\Models\LoadRequestItem;
use App\Models\IssueOrder;
use App\Models\IssueOrderItem;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Employee;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LoadRequestWebController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LoadRequest::with(['employee', 'warehouse', 'items.item'])
            ->orderByDesc('id');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%$s%")
                  ->orWhereHas('employee', fn($eq) => $eq->where('name', 'like', "%$s%"));
            });
        }

        $loadRequests = $query->paginate(15);

        return view('load-requests.index', compact('loadRequests'));
    }

    public function create()
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        $existingOpenOrder = LoadRequest::where('employee_id', $employee?->id)
            ->whereIn('status', ['draft', 'pending', 'approved', 'loading'])
            ->first();

        if ($existingOpenOrder) {
            return redirect()
                ->route('load-requests.index')
                ->with('error', "المندوب مينفعش يكون عنده اتنين أوامر تحميل مفتوحين - عندك أمر تحميل رقم {$existingOpenOrder->request_no} لسه مفتوح ({$existingOpenOrder->status}). لازم تغلق/تسلم الأمر الأول.");
        }

        $items = Item::with(['prices', 'itemUnits.unit'])->where('is_active', true)->orderBy('name_ar')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('load-requests.create', compact('items', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        $unitService = app(\App\Services\UnitConversionService::class);

        $repEmployeeId = $employee?->id;
        $existingOpenOrder = LoadRequest::where('employee_id', $repEmployeeId)
            ->whereIn('status', ['draft', 'pending', 'approved', 'loading'])
            ->first();

        if ($existingOpenOrder) {
            return back()->with('error', "المندوب مينفعش يكون عنده اتنين أوامر تحميل مفتوحين - عندك أمر تحميل رقم {$existingOpenOrder->request_no} لسه مفتوح ({$existingOpenOrder->status}). لازم تغلق/تسلم الأمر الأول.");
        }

        $result = DB::transaction(function () use ($request, $user, $employee, $unitService) {
            $loadRequest = LoadRequest::create([
                'company_id' => $user->company_id,
                'warehouse_id' => $request->warehouse_id,
                'employee_id' => $employee?->id,
                'request_date' => now()->toDateString(),
                'status' => 'pending',
                'notes' => $request->notes,
                'requested_by' => $employee?->id,
            ]);

            foreach ($request->items as $item) {
                $itemId = $item['item_id'];
                $qty = (float) $item['quantity'];
                $unitId = $item['unit_id'] ?? null;

                $resolved = $unitService->resolveUnit($itemId, $unitId);
                $finalUnitId = $resolved?->unit_id ?? $unitId;
                $conversionFactor = $resolved?->conversion_factor ?? 1;
                $baseQuantity = $unitService->toBase($itemId, $finalUnitId, $qty);

                LoadRequestItem::create([
                    'load_request_id' => $loadRequest->id,
                    'item_id' => $itemId,
                    'unit_id' => $finalUnitId,
                    'conversion_factor' => $conversionFactor,
                    'base_quantity' => $baseQuantity,
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => $qty * ($item['unit_price'] ?? 0),
                ]);
            }

            return $loadRequest;
        });

        return redirect()
            ->route('load-requests.show', $result->id)
            ->with('success', "تم إنشاء أمر التحميل {$result->request_no} بنجاح");
    }

    public function show(LoadRequest $loadRequest)
    {
        $loadRequest->load([
            'employee', 'warehouse', 'items.item', 'items.unit',
            'supervisorEmployee', 'requestedByEmployee', 'createByEmployee',
            'issueOrder.items.item',
        ]);

        return view('load-requests.show', compact('loadRequest'));
    }

    public function approve(LoadRequest $loadRequest)
    {
        $user = Auth::user();
        $isWarehouseKeeper = $user->hasRole(RoleNames::WAREHOUSE_KEEPER);
        $isAdmin = $user->isAdmin();

        if (!$isWarehouseKeeper && !$isAdmin) {
            return back()->with('error', 'ليس لديك صلاحية الموافقة على طلبات التحميل');
        }

        $loadRequest->load(['employee', 'warehouse', 'items.item', 'items.unit']);

        return view('load-requests.approve', compact('loadRequest'));
    }

    public function processApproval(Request $request, LoadRequest $loadRequest)
    {
        $user = Auth::user();
        $isWarehouseKeeper = $user->hasRole(RoleNames::WAREHOUSE_KEEPER);
        $isAdmin = $user->isAdmin();

        if (!$isWarehouseKeeper && !$isAdmin) {
            return back()->with('error', 'ليس لديك صلاحية تنفيذ هذه العملية');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::where('email', $user->email)->first();

        if ($request->action === 'reject') {
            $loadRequest->update([
                'status' => 'cancelled',
                'create_notes' => $request->notes ?? 'مرفوض من أمين المخزن',
            ]);
            return redirect()
                ->route('load-requests.show', $loadRequest->id)
                ->with('error', 'تم رفض طلب التحميل');
        }

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
        foreach ($loadRequest->items as $loadItem) {
            $available = $this->getWarehouseStock($warehouseId, $loadItem->item_id);
            if ($available < $loadItem->base_quantity) {
                $itemName = $loadItem->item?->name_ar ?? "صنف #{$loadItem->item_id}";
                $errors[] = "{$itemName}: المتاح {$available}، المطلوب {$loadItem->base_quantity}";
            }
        }

        if (!empty($errors)) {
            return back()->with('error', "الكميات غير كافية في المخزن:\n" . implode("\n", $errors));
        }

        DB::transaction(function () use ($loadRequest, $request, $employee, $user) {
            $loadRequest->update([
                'status' => 'approved',
                'supervisor_employee_id' => $employee?->id,
                'create_notes' => $request->notes ?? 'تمت الموافقة من أمين المخزن',
            ]);

            $issueOrder = IssueOrder::create([
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
                'notes' => "صادر بناءً على أمر التحميل {$loadRequest->request_no}",
            ]);

            foreach ($loadRequest->items as $loadItem) {
                $baseQty = (float) ($loadItem->base_quantity ?? 0);
                $cf = (float) ($loadItem->conversion_factor ?? 1);
                $baseUnitId = $loadItem->unit_id;

                if ($baseQty <= 0 && $cf > 0) {
                    $baseQty = (float) $loadItem->quantity * $cf;
                }

                IssueOrderItem::create([
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

            $type = \App\Models\InventoryTransactionType::where('code', 'ISSUE_ORDER')->first();
            if (!$type) {
                $type = \App\Models\InventoryTransactionType::firstOrCreate(
                    ['code' => 'ISSUE_ORDER'],
                    ['name' => 'أمر صرف', 'effect' => 'subtraction', 'is_active' => true]
                );
            }

            $txn = \App\Models\InventoryTransaction::create([
                'company_id' => $loadRequest->company_id,
                'warehouse_id' => $loadRequest->warehouse_id,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\InventoryTransaction::nextTransactionNo($loadRequest->company_id),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => IssueOrder::class,
                'reference_id' => $issueOrder->id,
                'notes' => "إذن صرف بناءً على أمر التحميل {$loadRequest->request_no}",
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

                \App\Models\InventoryTransactionItem::create([
                    'inventory_transaction_id' => $txn->id,
                    'item_id' => $itemId,
                    'unit_id' => $baseUnitId,
                    'conversion_factor' => $cf,
                    'qty' => -$baseQty,
                    'unit_cost' => $loadItem->unit_price,
                    'total_cost' => $loadItem->total_price,
                    'from_location_type' => 'warehouse',
                    'from_location_id'   => $loadRequest->warehouse_id,
                    'to_location_type'   => 'rep',
                    'to_location_id'     => $loadRequest->employee_id,
                ]);
            }

            $loadRequest->update(['status' => 'loading']);
        });

        return redirect()
            ->route('load-requests.show', $loadRequest->id)
            ->with('success', 'تمت الموافقة على الطلب وإنشاء إذن الصرف بنجاح');
    }

    public function destroy(LoadRequest $loadRequest)
    {
        if (!in_array($loadRequest->status, ['draft', 'pending'])) {
            return back()->with('error', 'لا يمكن حذف طلب بحالة ' . $loadRequest->status);
        }

        $loadRequest->delete();
        return redirect()
            ->route('load-requests.index')
            ->with('success', 'تم حذف طلب التحميل بنجاح');
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
}
