<?php
/**
 * =====================================================================
 * متحكم (Controller): DistributionPlanController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Distribution Plan
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Distribution Plan" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DistributionPlan;
use App\Models\Sales\DistributionPlanProduct;
use App\Models\Sales\DistributionPlanRep;
use App\Models\Sales\DistributionPlanCustomer;
use App\Models\Sales\DistributionPlanItem;
use App\Models\Inventory\IssueOrder;
use App\Models\Inventory\IssueOrderItem;
use App\Models\Sales\LoadRequest;
use App\Models\Sales\LoadRequestItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionItem;
use App\Models\Inventory\InventoryTransactionType;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\ItemUnit;
use App\Models\HR\Employee;
use App\Models\CRM\Customer;
use App\Support\DayOfWeekHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributionPlanController extends Controller
{
    /**
     * عرض قائمة سجلات (Distribution Plan) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DistributionPlan::with(['createdByEmployee', 'approvedByEmployee', 'products.item', 'reps.salesRep', 'reps.route'])
            ->orderByDesc('id');

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('plan_no', 'like', "%$s%")->orWhere('plan_name', 'like', "%$s%");
            });
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * عرض تفاصيل سجل محدد من (Distribution Plan) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $plan = DistributionPlan::with([
            'company', 'createdByEmployee', 'approvedByEmployee',
            'products.item',
            'reps.salesRep', 'reps.route',
            'reps.customers.customer',
            'reps.customers.items.item',
        ])->findOrFail($id);

        $planArray = $plan->toArray();
        $planArray['item_demand'] = $this->getItemDemand($plan);
        return response()->json($planArray);
    }

    /**
     * إنشاء سجل جديد لـ (Distribution Plan) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_name' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
            'history_months' => 'nullable|integer|min:1|max:24',
            'units_per_carton' => 'nullable|integer|min:1',
            'enforce_plan_limit' => 'sometimes|boolean',
            'products' => 'required|array|min:1',
            'products.*.item_id' => 'required|exists:items,id',
            'products.*.available_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();

        $productsData = $data['products'];
        $totalQty = collect($productsData)->sum('available_qty');

        $plan = DistributionPlan::create([
            'company_id' => $user->company_id,
            'plan_name' => $data['plan_name'] ?? null,
            'plan_date' => $data['plan_date'],
            'history_months' => $data['history_months'] ?? 6,
            'units_per_carton' => $data['units_per_carton'] ?? 50,
            'enforce_plan_limit' => $data['enforce_plan_limit'] ?? false,
            'total_quantity' => $totalQty,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $employee?->id,
        ]);

        foreach ($productsData as $p) {
            $ratio = $totalQty > 0 ? ($p['available_qty'] / $totalQty) * 100 : 0;
            DistributionPlanProduct::create([
                'distribution_plan_id' => $plan->id,
                'item_id' => $p['item_id'],
                'available_qty' => $p['available_qty'],
                'product_ratio' => round($ratio, 2),
            ]);
        }

        return response()->json($plan->load('products.item'), 201);
    }

    /**
     * تحديث بيانات سجل موجود من (Distribution Plan) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $plan = DistributionPlan::findOrFail($id);

        if ($plan->status !== 'draft') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† ØªØ¹Ø¯ÙŠÙ„ Ø®Ø·Ø© Ø¨Ø­Ø§Ù„Ø© ' . $plan->status], 422);
        }

        $data = $request->validate([
            'plan_name' => 'nullable|string|max:255',
            'products' => 'sometimes|array|min:1',
            'products.*.item_id' => 'required_with:products|exists:items,id',
            'products.*.available_qty' => 'required_with:products|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if (isset($data['plan_name'])) $plan->update(['plan_name' => $data['plan_name']]);
        if (isset($data['notes'])) $plan->update(['notes' => $data['notes']]);

        if (isset($data['products'])) {
            $plan->products()->delete();
            $totalQty = collect($data['products'])->sum('available_qty');
            foreach ($data['products'] as $p) {
                $ratio = $totalQty > 0 ? ($p['available_qty'] / $totalQty) * 100 : 0;
                DistributionPlanProduct::create([
                    'distribution_plan_id' => $plan->id,
                    'item_id' => $p['item_id'],
                    'available_qty' => $p['available_qty'],
                    'product_ratio' => round($ratio, 2),
                ]);
            }
            $plan->update(['total_quantity' => $totalQty]);
        }

        return response()->json($plan->load('products.item'));
    }

    /**
     * حذف سجل من (Distribution Plan) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $plan = DistributionPlan::findOrFail($id);

        if ($plan->status === 'applied') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø®Ø·Ø© Ù…ÙØ¹ØªÙ…Ø¯Ø©'], 422);
        }
        $plan->delete();
        return response()->json(null, 204);
    }

    /**
     * حساب / تلخيص بيانات (Distribution Plan) وإرجاع النتيجة.
     */
    public function calculate($id)
    {
        $plan = DistributionPlan::findOrFail($id);
        $unitsPerCarton = $plan->units_per_carton ?: 50;

        DB::beginTransaction();

        try {
            $companyId = $plan->company_id;

            $plan->reps()->delete();

            $products = $plan->products()->get();
            if ($products->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ù…Ù†ØªØ¬Ø§Øª ÙÙŠ Ø§Ù„Ø®Ø·Ø©'], 422);
            }

            $totalCartons = (float) $products->sum('available_qty');
            $totalUnits = $totalCartons * $unitsPerCarton;

            $productRatios = [];
            foreach ($products as $prod) {
                $productRatios[$prod->item_id] = $totalCartons > 0
                    ? (float) $prod->available_qty / $totalCartons
                    : 0;
            }

            $dayOfWeek = date('l', strtotime($plan->plan_date));
            $dayNumber = DayOfWeekHelper::nameToNumber($dayOfWeek);

            $repCustomerMap = [];
            $repRouteMap = [];
            $schedules = DB::table('route_schedules')
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->whereRaw("FIND_IN_SET(?, day_of_week)", [$dayNumber])
                ->get();

            // Bulk fetch route_customers for all routes
            $scheduleRouteIds = $schedules->pluck('route_id')->unique()->values();
            $routeCustomersMap = DB::table('route_customers')
                ->whereIn('route_id', $scheduleRouteIds)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->get()
                ->groupBy('route_id')
                ->map(fn($rows) => $rows->pluck('customer_id')->toArray());

            foreach ($schedules as $sch) {
                $routeId = $sch->route_id;
                $employeeId = $sch->user_id;
                $repRouteMap[$employeeId] = $routeId;
                $dayCustomers = $routeCustomersMap->get($routeId, []);
                foreach ($dayCustomers as $custId) {
                    $repCustomerMap[$custId] = $employeeId;
                }
            }

            $dayCustomerIds = collect(array_keys($repCustomerMap));
            if ($dayCustomerIds->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => "Ù„Ø§ ØªÙˆØ¬Ø¯ Ø®Ø·ÙˆØ· Ø³ÙŠØ± Ù„ÙŠÙˆÙ… $dayOfWeek"], 422);
            }

            $historyMonths = max(1, (int) $plan->history_months);
            $sinceDate = now()->subMonths($historyMonths)->toDateString();
            $itemIds = $products->pluck('item_id')->toArray();

            $invoiceItemAgg = DB::table('sales_invoice_items as sii')
                ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
                ->whereIn('sii.item_id', $itemIds)
                ->whereIn('si.customer_id', $dayCustomerIds)
                ->where('si.company_id', $companyId)
                ->whereNull('si.deleted_at')
                ->where('si.invoice_date', '>=', $sinceDate)
                ->select('si.customer_id', 'sii.item_id', DB::raw('SUM(sii.qty) as total_qty'), DB::raw('COUNT(DISTINCT si.id) as invoice_count'))
                ->groupBy('si.customer_id', 'sii.item_id')
                ->get();

            $customerItemDemand = [];
            foreach ($invoiceItemAgg as $row) {
                $custId = $row->customer_id;
                $itemId = $row->item_id;
                $avg = $row->invoice_count > 0 ? round($row->total_qty / $row->invoice_count, 2) : 0;

                if (!isset($customerItemDemand[$custId])) {
                    $customerItemDemand[$custId] = ['items' => [], 'total' => 0];
                }
                $customerItemDemand[$custId]['items'][$itemId] = $avg;
                $customerItemDemand[$custId]['total'] += $avg;
            }

            $customersWithDemand = $dayCustomerIds
                ->filter(fn($cid) => isset($customerItemDemand[$cid]) && $customerItemDemand[$cid]['total'] > 0);

            if ($customersWithDemand->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ø¨ÙŠØ¹Ø§Øª ÙƒØ§ÙÙŠØ© Ù…Ù† Ø§Ù„ÙÙˆØ§ØªÙŠØ±'], 422);
            }

            $totalAllCustomerAvg = 0;
            foreach ($customersWithDemand as $cid) {
                $totalAllCustomerAvg += $customerItemDemand[$cid]['total'];
            }

            if ($totalAllCustomerAvg == 0) {
                DB::rollBack();
                return response()->json(['message' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ø¨ÙŠØ¹Ø§Øª ÙƒØ§ÙÙŠØ©'], 422);
            }

            $repDemand = [];

            foreach ($customersWithDemand as $custId) {
                $demand = $customerItemDemand[$custId];
                $empId = $repCustomerMap[$custId];
                if (!isset($repDemand[$empId])) {
                    $repDemand[$empId] = ['total' => 0, 'customers' => []];
                }
                $repDemand[$empId]['total'] += $demand['total'];
                $repDemand[$empId]['customers'][] = [
                    'customer_id' => $custId,
                    'avg_monthly' => $demand['total'],
                    'items' => $demand['items'],
                ];
            }

            $allDemand = 0;
            foreach ($repDemand as $rd) {
                $allDemand += $rd['total'];
            }

            $allocFactor = $allDemand > 0 ? min(1.0, $totalAllCustomerAvg / $allDemand) : 1.0;

            $plan->update([
                'total_demand' => $allDemand,
                'allocation_factor' => round($allocFactor, 4),
                'status' => 'calculated',
            ]);

            $allCustPlans = [];

            foreach ($repDemand as $empId => $rd) {
                $repWeight = $allDemand > 0 ? $rd['total'] / $allDemand : 0;
                $repQuota = $totalCartons * $repWeight;

                $routeId = $repRouteMap[$empId] ?? null;
                $repPlan = DistributionPlanRep::create([
                    'distribution_plan_id' => $plan->id,
                    'sales_rep_id' => $empId,
                    'route_id' => $routeId,
                    'avg_monthly_sales' => round($rd['total'], 2),
                    'rep_weight' => round($repWeight, 4),
                    'total_quota' => round($repQuota, 2),
                ]);

                foreach ($rd['customers'] as $cd) {
                    $custId = $cd['customer_id'];
                    $custAvg = $cd['avg_monthly'];
                    $customerShare = $totalAllCustomerAvg > 0 ? $custAvg / $totalAllCustomerAvg : 0;
                    $custEntitlementUnits = $totalUnits * $customerShare;
                    $custEntitlementCartons = $custEntitlementUnits / $unitsPerCarton;

                    $custPlan = DistributionPlanCustomer::create([
                        'distribution_plan_id' => $plan->id,
                        'distribution_plan_rep_id' => $repPlan->id,
                        'customer_id' => $custId,
                        'avg_monthly_sales' => round($custAvg, 2),
                        'customer_weight' => round($customerShare, 4),
                        'total_quota' => round($custEntitlementCartons, 2),
                        'allocated_qty' => round($custEntitlementCartons, 2),
                        'final_qty' => round($custEntitlementCartons, 2),
                    ]);

                    $allCustPlans[] = [
                        'plan' => $custPlan,
                        'items' => $cd['items'],
                        'final_qty_cartons' => $custEntitlementCartons,
                        'rep_id' => $empId,
                    ];
                }
            }

            $unroundedEntries = [];
            $roundedEntries = [];
            $totalAllocatedUnits = 0;

            foreach ($allCustPlans as $cpData) {
                $custPlan = $cpData['plan'];
                $custFinalQtyCartons = $cpData['final_qty_cartons'];
                $custFinalQtyUnits = $custFinalQtyCartons * $unitsPerCarton;

                $productAllocations = [];
                $runningSum = 0;

                foreach ($products as $prod) {
                    $itemId = $prod->item_id;
                    $ratio = $productRatios[$itemId] ?? 0;
                    $exactUnits = $custFinalQtyUnits * $ratio;
                    $roundedUnits = (int) round($exactUnits);

                    $productAllocations[] = [
                        'item_id' => $itemId,
                        'exact_units' => $exactUnits,
                        'rounded_units' => $roundedUnits,
                        'ratio' => $ratio,
                    ];
                    $runningSum += $roundedUnits;
                }

                $roundingDiff = (int) round($custFinalQtyUnits) - $runningSum;
                if ($roundingDiff != 0 && !empty($productAllocations)) {
                    $maxIdx = 0;
                    $maxExact = 0;
                    foreach ($productAllocations as $idx => $pa) {
                        if ($pa['exact_units'] > $maxExact) {
                            $maxExact = $pa['exact_units'];
                            $maxIdx = $idx;
                        }
                    }
                    $productAllocations[$maxIdx]['rounded_units'] += $roundingDiff;
                }

                foreach ($productAllocations as $pa) {
                    $allocatedCartons = $pa['rounded_units'] / $unitsPerCarton;

                    $roundedEntries[] = [
                        'distribution_plan_id' => $plan->id,
                        'distribution_plan_customer_id' => $custPlan->id,
                        'item_id' => $pa['item_id'],
                        'historical_avg' => round($cpData['items'][$pa['item_id']] ?? 0, 2),
                        'historical_ratio' => round($pa['ratio'] * 100, 2),
                        'allocated_qty' => round($allocatedCartons, 4),
                        'final_qty' => round($allocatedCartons, 4),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $totalAllocatedUnits += $pa['rounded_units'];
                }
            }

            // Correct per-product totals to match available quantities
            foreach ($products as $prod) {
                $itemId = $prod->item_id;
                $expectedCartons = (float) $prod->available_qty;
                $actualCartons = 0;
                foreach ($roundedEntries as $re) {
                    if ($re['item_id'] == $itemId) {
                        $actualCartons += $re['final_qty'];
                    }
                }
                $diffCartons = round($actualCartons - $expectedCartons, 4);
                if (abs($diffCartons) < 0.0001) continue;

                // Find the largest allocation for this product
                $maxIdx = -1;
                $maxQty = 0;
                foreach ($roundedEntries as $idx => $re) {
                    if ($re['item_id'] == $itemId && $re['final_qty'] > $maxQty) {
                        $maxQty = $re['final_qty'];
                        $maxIdx = $idx;
                    }
                }
                if ($maxIdx >= 0) {
                    $roundedEntries[$maxIdx]['final_qty'] = round($roundedEntries[$maxIdx]['final_qty'] - $diffCartons, 4);
                    $roundedEntries[$maxIdx]['allocated_qty'] = $roundedEntries[$maxIdx]['final_qty'];
                }
            }

            if (!empty($roundedEntries)) {
                DB::table('distribution_plan_items')->insert($roundedEntries);
            }

            // Sync customer final_qty to match sum of their items
            $custIds = array_unique(array_column($roundedEntries, 'distribution_plan_customer_id'));
            foreach ($custIds as $cid) {
                $sumItems = DB::table('distribution_plan_items')
                    ->where('distribution_plan_customer_id', $cid)
                    ->sum('final_qty');
                DB::table('distribution_plan_customers')
                    ->where('id', $cid)
                    ->update(['final_qty' => round($sumItems, 2)]);
            }

            DB::commit();

            $plan->refresh();
            $plan->load([
                'products.item', 'reps.salesRep', 'reps.route',
                'reps.customers.customer', 'reps.customers.items.item',
            ]);
            $planArr = $plan->toArray();
            $planArr['item_demand'] = $this->getItemDemand($plan);

            return response()->json([
                'message' => 'ØªÙ… Ø­Ø³Ø§Ø¨ Ø§Ù„ØªÙˆØ²ÙŠØ¹ Ø¨Ù†Ø¬Ø§Ø­',
                'plan' => $planArr,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ø®Ø·Ø£ ÙÙŠ Ø§Ù„Ø­Ø³Ø§Ø¨: ' . $e->getMessage()], 500);
        }
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Distribution Plan).
     */
    public function approve(Request $request, $id)
    {
        $plan = DistributionPlan::findOrFail($id);

        if ($plan->status !== 'calculated') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ Ø¥Ù„Ø§ Ø¨Ø¹Ø¯ Ø§Ù„Ø­Ø³Ø§Ø¨'], 422);
        }

        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();
        $loadRequestNos = [];

        DB::transaction(function () use ($plan, $employee, &$loadRequestNos) {
            $loadRequestNos = [];
            $unitsPerCarton = $plan->units_per_carton ?: 50;

            $warehouse = Warehouse::where('company_id', $plan->company_id)
                ->where('is_default', true)
                ->first();
            if (!$warehouse) {
                $warehouse = Warehouse::where('company_id', $plan->company_id)->first();
            }
            $warehouseId = $warehouse?->id;

            $plan->load(['reps.customers.items']);

            // Bulk fetch ItemUnits for all items (avoid N+1)
            $allItemIds = [];
            foreach ($plan->reps as $rep) {
                foreach ($rep->customers as $customer) {
                    foreach ($customer->items as $item) {
                        $allItemIds[] = $item->item_id;
                    }
                }
            }
            $allItemIds = array_unique($allItemIds);

            $itemUnitsMap = ItemUnit::whereIn('item_id', $allItemIds)
                ->get()
                ->groupBy('item_id')
                ->map(function ($units) {
                    $default = $units->firstWhere('is_default', true);
                    return $default ?? $units->first();
                });

            foreach ($plan->reps as $rep) {
                $items = [];
                $totalQty = 0;
                $totalAmount = 0;

                foreach ($rep->customers as $customer) {
                    foreach ($customer->items as $item) {
                        $itemId = $item->item_id;
                        $cartons = (float) $item->final_qty;
                        $qty = $cartons * $unitsPerCarton;

                        $itemUnit = $itemUnitsMap->get($itemId);
                        $unitPrice = (float) ($itemUnit?->sale_price ?? $itemUnit?->purchase_price ?? 0);
                        $totalPrice = $qty * $unitPrice;

                        $items[] = [
                            'item_id' => $itemId,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'unit_id' => $itemUnit?->unit_id,
                            'total_price' => $totalPrice,
                        ];
                        $totalQty += $qty;
                        $totalAmount += $totalPrice;
                    }
                }

                if (empty($items)) {
                    continue;
                }

                $loadRequest = LoadRequest::create([
                    'company_id' => $plan->company_id,
                    'warehouse_id' => $warehouseId,
                    'user_id' => $rep->sales_rep_id,
                    'request_date' => now()->toDateString(),
                    'status' => 'pending',
                    'total_items_count' => count($items),
                    'total_quantity' => $totalQty,
                    'total_amount' => $totalAmount,
                    'requested_by' => $employee?->id,
                    'notes' => "Ø£Ù…Ø± ØªØ­Ù…ÙŠÙ„ Ø¨Ù†Ø§Ø¡Ù‹ Ø¹Ù„Ù‰ Ø®Ø·Ø© Ø§Ù„ØªÙˆØ²ÙŠØ¹ {$plan->plan_no}",
                ]);

                foreach ($items as $itemData) {
                    LoadRequestItem::create([
                        'load_request_id' => $loadRequest->id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'unit_id' => $itemData['unit_id'],
                        'total_price' => $itemData['total_price'],
                    ]);
                }

                $loadRequestNos[] = $loadRequest->request_no;
            }

            $plan->update([
                'status' => 'approved',
                'approved_by' => $employee?->id,
                'approved_at' => now(),
            ]);
        });

        $plan->refresh()->load(['products.item', 'reps.salesRep', 'reps.route', 'reps.customers.customer', 'reps.customers.items.item']);
        $planArr = $plan->toArray();
        $planArr['item_demand'] = $this->getItemDemand($plan);
        return response()->json([
            'message' => 'ØªÙ… Ø§Ø¹ØªÙ…Ø§Ø¯ Ø®Ø·Ø© Ø§Ù„ØªÙˆØ²ÙŠØ¹ ÙˆØ¥Ù†Ø´Ø§Ø¡ ' . count($loadRequestNos) . ' Ø£ÙˆØ§Ù…Ø± ØªØ­Ù…ÙŠÙ„ (' . implode(', ', $loadRequestNos) . ')',
            'plan' => $planArr,
        ]);
    }

    /**
     * دالة معالجة: reopen — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Distribution Plan).
     */
    public function reopen(Request $request, $id)
    {
        $plan = DistributionPlan::findOrFail($id);

        if ($plan->status !== 'approved') {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† ÙØªØ­ Ø¥Ù„Ø§ Ø§Ù„Ø®Ø·Ø© Ø§Ù„Ù…Ø¹ØªÙ…Ø¯Ø©'], 422);
        }

        DB::transaction(function () use ($plan) {
            $txnIds = DB::table('inventory_transactions')
                ->where('reference_type', \App\Models\Inventory\IssueOrder::class)
                ->whereIn('reference_id', function ($q) use ($plan) {
                    $q->select('id')
                        ->from('issue_orders')
                        ->where('notes', 'like', "%{$plan->plan_no}%");
                })
                ->pluck('id');

            if ($txnIds->isNotEmpty()) {
                DB::table('inventory_transaction_items')
                    ->whereIn('inventory_transaction_id', $txnIds)
                    ->delete();
                DB::table('inventory_transactions')
                    ->whereIn('id', $txnIds)
                    ->delete();
            }

            $ioIds = DB::table('issue_orders')
                ->where('notes', 'like', "%{$plan->plan_no}%")
                ->pluck('id');
            if ($ioIds->isNotEmpty()) {
                DB::table('issue_order_items')
                    ->whereIn('issue_order_id', $ioIds)
                    ->delete();
                DB::table('issue_orders')
                    ->whereIn('id', $ioIds)
                    ->delete();
            }

            $lrIds = DB::table('load_requests')
                ->where('notes', 'like', "%{$plan->plan_no}%")
                ->pluck('id');
            if ($lrIds->isNotEmpty()) {
                DB::table('load_request_items')
                    ->whereIn('load_request_id', $lrIds)
                    ->delete();
                DB::table('load_requests')
                    ->whereIn('id', $lrIds)
                    ->delete();
            }

            $plan->update([
                'status' => 'calculated',
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });

        $plan->refresh()->load(['products.item', 'reps.salesRep', 'reps.route', 'reps.customers.customer', 'reps.customers.items.item']);
        $planArr = $plan->toArray();
        $planArr['item_demand'] = $this->getItemDemand($plan);
        return response()->json([
            'message' => 'ØªÙ… ÙØªØ­ Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ ÙˆØ­Ø°Ù Ø£ÙˆØ§Ù…Ø± Ø§Ù„ØªØ­Ù…ÙŠÙ„',
            'plan' => $planArr,
        ]);
    }

    /**
     * دالة معالجة: updateCustomerQty — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Distribution Plan).
     */
    public function updateCustomerQty(Request $request, $id, $customerId)
    {
        $plan = DistributionPlan::findOrFail($id);
        $customer = \App\Models\Sales\DistributionPlanCustomer::findOrFail($customerId);

        $data = $request->validate([
            'final_qty' => 'required|numeric|min:0',
        ]);

        $customer->update([
            'final_qty' => $data['final_qty'],
            'is_manual_override' => true,
        ]);

        $planProducts = $plan->products()->get();
        $productRatios = [];
        foreach ($planProducts as $p) {
            $productRatios[$p->item_id] = $p->product_ratio;
        }

        $items = $customer->items()->get();
        $itemTotal = $items->sum('historical_avg');

        if ($itemTotal > 0) {
            foreach ($items as $item) {
                $ratio = $itemTotal > 0 ? ($item->historical_avg / $itemTotal) * 100 : ($productRatios[$item->item_id] ?? 0);
                $newQty = $data['final_qty'] * ($ratio / 100);
                $item->update(['final_qty' => round($newQty, 2)]);
            }
        } else {
            $ratioEach = count($items) > 0 ? 100 / count($items) : 0;
            foreach ($items as $item) {
                $newQty = $data['final_qty'] * ($ratioEach / 100);
                $item->update(['final_qty' => round($newQty, 2)]);
            }
        }

        return response()->json(['message' => 'ØªÙ… Ø§Ù„ØªØ­Ø¯ÙŠØ«', 'customer' => $customer->fresh()->load('items.item')]);
    }

    /**
     * دالة معالجة: updateItemQty — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Distribution Plan).
     */
    public function updateItemQty(Request $request, $id, $itemId)
    {
        $plan = DistributionPlan::findOrFail($id);
        $item = \App\Models\Sales\DistributionPlanItem::findOrFail($itemId);

        $data = $request->validate([
            'final_qty' => 'required|numeric|min:0',
        ]);

        $item->update([
            'final_qty' => $data['final_qty'],
            'is_manual_override' => true,
        ]);

        $custPlan = $item->customerPlan;
        $totalItems = $custPlan->items()->sum('final_qty');
        $custPlan->update(['final_qty' => $totalItems]);

        return response()->json(['message' => 'ØªÙ… Ø§Ù„ØªØ­Ø¯ÙŠØ«', 'item' => $item->fresh()]);
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Distribution Plan) حسب الطلب.
     */
    private function getItemDemand(DistributionPlan $plan): array
    {
        $products = $plan->products;
        $itemIds = $products->pluck('item_id')->toArray();
        $customerIds = $plan->reps->flatMap->customers->pluck('customer_id')->unique()->toArray();

        if (empty($itemIds) || empty($customerIds)) {
            return [];
        }

        $items = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->whereIn('sii.item_id', $itemIds)
            ->whereIn('si.customer_id', $customerIds)
            ->where('si.company_id', $plan->company_id)
            ->whereNull('si.deleted_at')
            ->select('sii.item_id', DB::raw('SUM(sii.qty) as total_qty'), DB::raw('COUNT(DISTINCT si.id) as invoice_count'))
            ->groupBy('sii.item_id')
            ->get();

        $itemDemand = [];
        foreach ($items as $row) {
            $avg = $row->invoice_count > 0 ? round($row->total_qty / $row->invoice_count, 2) : 0;
            $itemDemand[] = [
                'item_id' => $row->item_id,
                'total_qty' => (float) $row->total_qty,
                'invoice_count' => $row->invoice_count,
                'avg_demand' => $avg,
            ];
        }
        return $itemDemand;
    }
}
