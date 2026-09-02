<?php
/**
 * =====================================================================
 * متحكم (Controller): LoadRequestWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Load Request Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Load Request Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
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
    /**
     * عرض قائمة سجلات (Load Request Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = LoadRequest::with(['employee', 'warehouse', 'items.item', 'parentRequest'])
            ->orderByDesc('id');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->load_type) {
            $query->where('load_type', $request->load_type);
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

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Load Request Web).
     */
    public function create()
    {
        $user = Auth::user();
        $employee = Employee::where('email', $user->email)->first();

        $existingOpenOrder = LoadRequest::where('user_id', $employee?->id)
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

    /**
     * إنشاء سجل جديد لـ (Load Request Web) بعد التحقق من صحة البيانات المدخلة.
     */
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
        $existingOpenOrder = LoadRequest::where('user_id', $repEmployeeId)
            ->whereIn('status', ['draft', 'pending', 'approved', 'loading'])
            ->first();

        if ($existingOpenOrder) {
            return back()->with('error', "المندوب مينفعش يكون عنده اتنين أوامر تحميل مفتوحين - عندك أمر تحميل رقم {$existingOpenOrder->request_no} لسه مفتوح ({$existingOpenOrder->status}). لازم تغلق/تسلم الأمر الأول.");
        }

        $result = DB::transaction(function () use ($request, $user, $employee, $unitService) {
            $loadRequest = LoadRequest::create([
                'company_id' => $user->company_id,
                'warehouse_id' => $request->warehouse_id,
                'user_id' => $employee?->id,
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

    /**
     * عرض فورم إنشاء أمر تحميل تكميلى مرتبط بأمر تحميل موجود (مفتوح).
     * يسمح بإضافة أصناف لمخزون المندوب بدون ارتجاع الأذن الأصلي.
     */
    public function createComplementary(LoadRequest $loadRequest)
    {
        $items = Item::with(['prices', 'itemUnits.unit'])->where('is_active', true)->orderBy('name_ar')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('load-requests.complementary-create', compact('items', 'warehouses', 'loadRequest'));
    }

    /**
     * إنشاء أمر تحميل تكميلى (يضاف لمخزون المندوب ويرسل للهاند هولد).
     * يتجاوز قيد "مندوب واحد = أمر تحميل مفتوح واحد".
     */
    public function storeComplementary(Request $request, LoadRequest $parent)
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
        $unitService = app(\App\Services\UnitConversionService::class);

        $repEmployeeId = $parent->user_id;

        $result = DB::transaction(function () use ($request, $user, $parent, $unitService, $repEmployeeId) {
            $loadRequest = LoadRequest::create([
                'company_id' => $user->company_id,
                'warehouse_id' => $request->warehouse_id,
                'user_id' => $repEmployeeId,
                'request_no' => $parent->request_no,
                'parent_load_request_id' => $parent->id,
                'load_type' => 'complementary',
                'request_date' => now()->toDateString(),
                'status' => 'pending',
                'notes' => $request->notes,
                'requested_by' => $repEmployeeId,
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
            ->with('success', "تم إنشاء أمر التحميل التكميلى {$result->request_no} بنجاح");
    }

    /**
     * عرض تفاصيل سجل محدد من (Load Request Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(LoadRequest $loadRequest)
    {
        $loadRequest->load([
            'employee', 'warehouse', 'items.item', 'items.unit',
            'supervisorEmployee', 'requestedByEmployee', 'createByEmployee',
            'issueOrder.items.item',
            'complementaryRequests.employee', 'complementaryRequests.warehouse',
        ]);

        return view('load-requests.show', compact('loadRequest'));
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Load Request Web).
     */
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

    /**
     * دالة معالجة: processApproval — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Load Request Web).
     */
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
                'supervisor_user_id' => $employee?->id,
                'create_notes' => $request->notes ?? 'تمت الموافقة من أمين المخزن',
            ]);

            $issueNo = \App\Models\NumberSeries::nextNumber(
                companyId: (int) $loadRequest->company_id,
                documentType: 'issue_order',
            );

            $issueOrder = IssueOrder::create([
                'company_id' => $loadRequest->company_id,
                'warehouse_id' => $loadRequest->warehouse_id,
                'load_request_id' => $loadRequest->id,
                'employee_id' => $loadRequest->employee_id,
                'issue_no' => $issueNo,
                'issue_date' => now()->toDateString(),
                'issue_time' => now()->toTimeString(),
                'user_id' => $loadRequest->user_id,
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
                    'to_location_id'     => $loadRequest->user_id,
                ]);
            }

            $loadRequest->update(['status' => 'loading']);
        });

        return redirect()
            ->route('load-requests.show', $loadRequest->id)
            ->with('success', 'تمت الموافقة على الطلب وإنشاء إذن الصرف بنجاح');
    }

    /**
     * إلغاء أمر تحميل معتمد وإرجاع الكمية للمخزن.
     */
    public function cancel(LoadRequest $loadRequest)
    {
        if (!in_array($loadRequest->status, ['approved', 'loading', 'loaded'])) {
            return back()->with('error', 'لا يمكن إلغاء أمر التحميل في الحالة الحالية');
        }

        $issueOrder = IssueOrder::where('load_request_id', $loadRequest->id)->whereNull('deleted_at')->first();

        DB::transaction(function () use ($loadRequest, $issueOrder) {
            $warehouseId = $loadRequest->warehouse_id;
            $companyId = $loadRequest->company_id;
            $employeeId = $loadRequest->user_id;

            $type = \App\Models\InventoryTransactionType::where('code', 'RETURN_TO_WAREHOUSE')->first();
            if (!$type) {
                $type = \App\Models\InventoryTransactionType::firstOrCreate(
                    ['code' => 'RETURN_TO_WAREHOUSE'],
                    ['name' => 'إرجاع لأمر التحميل للمخزن', 'effect' => 'addition', 'is_active' => true]
                );
            }

            $txn = \App\Models\InventoryTransaction::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'transaction_type_id' => $type->id,
                'transaction_no' => \App\Models\InventoryTransaction::nextTransactionNo($companyId),
                'transaction_date' => now()->toDateString(),
                'transaction_time' => now()->format('H:i:s'),
                'reference_type' => \App\Models\LoadRequest::class,
                'reference_id' => $loadRequest->id,
                'notes' => "إلغاء أمر التحميل {$loadRequest->request_no} وإرجاع للمخزن",
                'status' => 'posted',
                'created_by' => Auth::id(),
            ]);

            if ($issueOrder) {
                $items = IssueOrderItem::where('issue_order_id', $issueOrder->id)->whereNull('deleted_at')->get();

                foreach ($items as $item) {
                    $baseQty = (float) ($item->base_quantity ?? 0);
                    if ($baseQty <= 0) {
                        $cf = (float) ($item->conversion_factor ?? 1);
                        $baseQty = (float) ($item->issued_quantity ?? 0) * ($cf > 0 ? $cf : 1);
                    }
                    if ($baseQty <= 0) continue;

                    $unitService = app(\App\Services\UnitConversionService::class);
                    $baseUnitId = $unitService->getBaseUnitId($item->item_id) ?? $item->unit_id;

                    \App\Models\InventoryTransactionItem::create([
                        'inventory_transaction_id' => $txn->id,
                        'item_id' => $item->item_id,
                        'unit_id' => $baseUnitId,
                        'conversion_factor' => (float) ($item->conversion_factor ?? 1),
                        'qty' => $baseQty,
                        'unit_cost' => $item->purchase_price,
                        'total_cost' => $item->total_amount,
                        'from_location_type' => 'rep',
                        'from_location_id' => $employeeId,
                        'to_location_type' => 'warehouse',
                        'to_location_id' => $warehouseId,
                    ]);

                    $distribution = \App\Models\RepItemDistribution::where('company_id', $companyId)
                        ->where('user_id', $employeeId)
                        ->where('item_id', $item->item_id)
                        ->where('issue_order_id', $issueOrder->id)
                        ->where('status', 'active')
                        ->latest('id')
                        ->first();

                    if ($distribution) {
                        $distribution->update([
                            'loaded_qty' => max(0, $distribution->loaded_qty - $baseQty),
                            'remaining_qty' => max(0, $distribution->remaining_qty - $baseQty),
                        ]);
                    }
                }

                $issueOrder->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);
            }

            $loadRequest->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('load-requests.show', $loadRequest->id)
            ->with('success', 'تم إلغاء أمر التحميل وإرجاع الكمية للمخزن بنجاح');
    }

    /**
     * حذف سجل من (Load Request Web) مع مراعاة قواعد العمل قبل الحذف.
     */
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

    /**
     * جلب / استعلام بيانات مخصصة لـ (Load Request Web) حسب الطلب.
     */
    protected function getWarehouseStock(int $warehouseId, int $itemId): float
    {
        $txnQty = \App\Models\InventoryTransactionItem::query()
            ->selectRaw('COALESCE(SUM(inventory_transaction_items.qty), 0) as total')
            ->join('inventory_transactions', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->where('inventory_transaction_items.item_id', $itemId)
            ->where('inventory_transactions.warehouse_id', $warehouseId)
            ->where('inventory_transactions.status', 'posted')
            ->whereNull('inventory_transactions.deleted_at')
            ->value('total');

        $unitService = app(\App\Services\UnitConversionService::class);

        $obRecords = \App\Models\InventoryOpeningBalance::query()
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
