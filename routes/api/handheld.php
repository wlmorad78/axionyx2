<?php

use Illuminate\Support\Facades\Route as RouteFacade;
use App\Http\Controllers\Api\HandheldController;

RouteFacade::post('handheld/bootstrap', [HandheldController::class, 'bootstrap']);
RouteFacade::post('handheld/sync', [HandheldController::class, 'sync']);

use App\Models\Customer;
use App\Models\RouteCustomer;
use App\Models\RouteSchedule;
use App\Models\Route;
use App\Models\RouteAssignment;
use App\Models\Employee;
use App\Models\Representative;
use App\Models\Item;

use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Models\Warehouse;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\CustomerVisit;
use App\Models\Device;
use App\Models\RepItemDistribution;
use App\Models\IssueOrder;
use App\Services\UnitConversionService;
use App\Support\DayOfWeekHelper;
use Illuminate\Support\Facades\DB;

if (!function_exists('resolveEmployee')) {
    function resolveEmployee(\Illuminate\Http\Request $request) {
        $salesmanUserId = $request->input('_salesman_id') ?? $request->header('X-Salesman-Id');

        if ($salesmanUserId) {
            $salesmanUser = \App\Models\User::find($salesmanUserId);

            if ($salesmanUser) {
                $employee = $salesmanUser->employee;

                if (!$employee) {
                    $employee = Employee::where('email', $salesmanUser->email)->first();
                }

                if (!$employee) {
                    $representative = Representative::where('user_id', $salesmanUser->id)->first();

                    if ($representative) {
                        $employee = Employee::where(
                            'national_id',
                            $representative->code
                        )->first();
                    }
                }

                return $employee;
            }
        }

        return null;
    }
}

if (!function_exists('calculateCustomerBalance')) {
    function calculateCustomerBalance($customerId, $companyId) {
        $allInvoices = SalesInvoice::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(net_total), 0) as total_invoiced, COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $collectionsBalance = \App\Models\Sales\Collection::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_collections')
            ->first();

        $invoiceBalance = (float) $allInvoices->total_paid - (float) $allInvoices->total_invoiced;
        $collectionsEffect = -1 * (float) $collectionsBalance->total_collections;

        return round($invoiceBalance + $collectionsEffect, 2);
    }
}

RouteFacade::get('handheld/route-lines', function (\Illuminate\Http\Request $request) {
    $employee = resolveEmployee($request);
    $deviceDay = $request->input('day', now()->format('l'));
    $dayNumber = DayOfWeekHelper::nameToNumber($deviceDay) ?? DayOfWeekHelper::todayNumber();

    if (!$employee) {
        return response()->json(['data' => []]);
    }

    $delegatePhone = $employee->phone ?? null;
    $delegateMobile = $employee->mobile ?? null;

    $supervisorPhone = null;
    $supervisorMobile = null;
    $salesmanAssignment = DB::table('salesman_assignments')
        ->where('employee_id', $employee->id)
        ->where('job_role', 'salesman')
        ->where('is_active', true)
        ->first();

    if ($salesmanAssignment && $salesmanAssignment->parent_assignment_id) {
        $supervisorAssignment = DB::table('salesman_assignments')
            ->where('id', $salesmanAssignment->parent_assignment_id)
            ->first();
        if ($supervisorAssignment) {
            $supervisor = Employee::where('id', $supervisorAssignment->employee_id)->first();
            $supervisorPhone = $supervisor?->phone ?? null;
            $supervisorMobile = $supervisor?->mobile ?? null;
        }
    }

    $scheduleRouteIds = RouteSchedule::where('is_active', true)
        ->where('employee_id', $employee->id)
        ->whereRaw("INSTR(',' || day_of_week || ',', ',' || ? || ',') > 0", [$dayNumber])
        ->pluck('route_id');

    $assignmentRouteIds = RouteAssignment::where('driver_id', $employee->id)
        ->whereDate('assignment_date', now()->toDateString())
        ->where('is_active', true)
        ->pluck('route_id');

    $routeIds = $scheduleRouteIds->merge($assignmentRouteIds)->unique();

    if ($routeIds->isEmpty()) {
        return response()->json(['data' => []]);
    }

    $routes = Route::where('is_active', true)
        ->whereIn('id', $routeIds)
        ->with(['customers.customer', 'salesTerritory'])
        ->get();

    $data = $routes->map(function ($route) use ($delegatePhone, $delegateMobile, $supervisorPhone, $supervisorMobile) {
        $customers = $route->customers
            ->filter(fn($rc) => $rc->customer && !$rc->customer->trashed())
            ->sortBy('visit_order')
            ->values();

        return [
            'id' => $route->id,
            'name_ar' => $route->name_ar,
            'name_en' => $route->name_en,
            'code' => $route->code,
            'sales_territory_id' => $route->sales_territory_id,
            'sales_territory_name' => $route->salesTerritory?->name_ar,
            'customers_count' => $customers->count(),
            'customers' => $customers->map(fn($rc) => [
                'id' => $rc->customer->id,
                'name_ar' => $rc->customer->name_ar,
                'code' => $rc->customer->code,
                'phone' => $rc->customer->phone,
                'mobile' => $rc->customer->mobile,
                'tax_number' => $rc->customer->tax_number ?: $rc->customer->national_id,
                'national_id' => $rc->customer->national_id,
                'address_line' => $rc->customer->address_line,
                'governorate' => $rc->customer->governorate?->name_ar,
                'city' => $rc->customer->city?->name_ar,
                'area' => $rc->customer->area?->name_ar,
                'visit_order' => $rc->visit_order,
                'visit_frequency' => $rc->visit_frequency,
                'delegate_phone' => $delegatePhone,
                'delegate_mobile' => $delegateMobile,
                'supervisor_phone' => $supervisorPhone,
                'supervisor_mobile' => $supervisorMobile,
            ])->values(),
        ];
    });

    return response()->json(['data' => $data]);
});

RouteFacade::post('handheld/close-permit', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $items = $request->input('items', []);
    $notes = $request->input('notes', '');
    $branchId = $request->input('branch_id');

    if (empty($items)) {
        return response()->json(['message' => 'لا توجد أصناف لإغلاق الإذن'], 422);
    }

    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'لم يتم العثور على بيانات المندوب'], 422);
    }

    $warehouse = Warehouse::where('company_id', $user->company_id)
        ->where('is_active', true)
        ->first();

    if (!$warehouse) {
        return response()->json(['message' => 'لا يوجد مخزن نشط'], 422);
    }

    $result = DB::transaction(function () use ($user, $items, $notes, $branchId, $employee, $warehouse) {
        $totalItemsCount = 0;
        $totalQuantity = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            if ($qty <= 0) continue;
            $totalItemsCount++;
            $totalQuantity += $qty;
            $totalAmount += $qty * $price;
        }

        $activeIssueOrder = \App\Models\IssueOrder::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'issued'])
            ->whereNull('received_by')
            ->latest('id')
            ->first();

        $returnOrder = ReturnOrder::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'warehouse_id' => $warehouse->id,
            'issue_order_id' => $activeIssueOrder?->id,
            'return_type' => 'excess',
            'return_date' => now()->toDateString(),
            'employee_id' => $employee->id,
            'status_id' => 'pending',
            'total_items_count' => $totalItemsCount,
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
            'notes' => $notes ?: 'إغلاق إذن التحميل',
        ]);

        $issueItemsMap = [];
        if ($activeIssueOrder) {
            $issueItems = \App\Models\IssueOrderItem::where('issue_order_id', $activeIssueOrder->id)->get();
            foreach ($issueItems as $ii) {
                $issueItemsMap[$ii->item_id] = [
                    'item_unit_id' => $ii->item_unit_id,
                    'issued_quantity' => (float) $ii->issued_quantity,
                    'sales_price' => (float) $ii->sales_price,
                ];
            }
        }

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            if ($qty <= 0) continue;

            $itemId = $item['item_id'];
            $issueData = $issueItemsMap[$itemId] ?? null;
            $itemUnitId = $item['unit_id'] ?? $item['item_unit_id'] ?? $issueData['item_unit_id'] ?? null;
            $loadedQty = $issueData['issued_quantity'] ?? 0;
            $soldQty = max(0, $loadedQty - $qty);

            ReturnOrderItem::create([
                'return_order_id' => $returnOrder->id,
                'item_id' => $itemId,
                'item_unit_id' => $itemUnitId,
                'returned_quantity' => $qty,
                'sold_quantity' => $soldQty,
                'sales_price' => $price,
                'line_total' => $qty * $price,
                'return_condition' => 'good',
            ]);
        }

        \App\Models\IssueOrder::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'issued'])
            ->whereNull('received_by')
            ->update([
                'status' => 'delivered',
                'received_by' => $employee->id,
                'received_at' => now(),
            ]);

        $issueOrder = \App\Models\IssueOrder::where('employee_id', $employee->id)
            ->whereIn('status', ['delivered'])
            ->where('received_by', $employee->id)
            ->latest('id')
            ->first();

        if ($issueOrder) {
            $issueItems = \App\Models\IssueOrderItem::where('issue_order_id', $issueOrder->id)->get();
            $returnItemsMap = collect($items)->keyBy('item_id');

            foreach ($issueItems as $issueItem) {
                $returnedQty = 0;
                $returnedPrice = (float) $issueItem->sales_price;
                if (isset($returnItemsMap[$issueItem->item_id])) {
                    $returnedQty = (float) $returnItemsMap[$issueItem->item_id]['qty'];
                    $returnedPrice = (float) $returnItemsMap[$issueItem->item_id]['price'];
                }

                $loadedQty = (float) $issueItem->issued_quantity;
                $soldQty = $loadedQty - $returnedQty;

                RepItemDistribution::create([
                    'company_id' => $user->company_id,
                    'employee_id' => $employee->id,
                    'item_id' => $issueItem->item_id,
                    'issue_order_id' => $issueOrder->id,
                    'loaded_qty' => $loadedQty,
                    'sold_qty' => max(0, $soldQty),
                    'returned_qty' => $returnedQty,
                    'remaining_qty' => 0,
                    'unit_price' => $returnedPrice,
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
            }
        }

        return $returnOrder->load('items.item');
    });

    return response()->json([
        'message' => 'تم إغلاق الإذن وإرسال طلب الارتجاع للمخزن',
        'data' => $result,
    ], 201);
});

RouteFacade::get('handheld/my-return-orders', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $employee = resolveEmployee($request);

    if ($employee) {
        $orders = ReturnOrder::where('employee_id', $employee->id)
            ->with(['items.item', 'items.unit', 'issueOrder', 'warehouse'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        if ($orders->isNotEmpty()) {
            return response()->json(['data' => $orders]);
        }
    }

    $orders = ReturnOrder::where('company_id', $user->company_id)
        ->with(['items.item', 'items.unit', 'issueOrder', 'warehouse'])
        ->orderByDesc('id')
        ->limit(20)
        ->get();

    return response()->json(['data' => $orders]);
});

RouteFacade::get('handheld/return-orders/pending', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $orders = ReturnOrder::where('status_id', 'pending')
        ->with(['items.item', 'employee', 'warehouse'])
        ->orderByDesc('id')
        ->get();

    return response()->json(['data' => $orders]);
});

RouteFacade::post('handheld/return-orders/{id}/approve', function (\Illuminate\Http\Request $request, $id) {
    $user = $request->user();
    $returnOrder = ReturnOrder::findOrFail($id);

    if ($returnOrder->status_id !== 'pending') {
        return response()->json(['message' => 'هذا الطلب ليس قيد المراجعة'], 422);
    }

    $employee = resolveEmployee($request);

    $result = DB::transaction(function () use ($returnOrder, $employee, $user) {
        $unitService = app(UnitConversionService::class);

        $returnOrder->update([
            'status_id' => 'approved',
            'approved_by' => $employee?->id,
            'approved_at' => now(),
        ]);

        if ($returnOrder->load_request_id) {
            \App\Models\LoadRequest::where('id', $returnOrder->load_request_id)
                ->update(['status' => 'closed']);
        }

        $type = InventoryTransactionType::firstOrCreate(
            ['code' => 'RETURN'],
            ['name' => 'Return Order', 'effect' => 'addition', 'is_active' => true]
        );

        $txn = InventoryTransaction::create([
            'company_id' => $returnOrder->company_id,
            'warehouse_id' => $returnOrder->warehouse_id,
            'transaction_type_id' => $type->id,
            'transaction_no' => InventoryTransaction::nextTransactionNo($returnOrder->company_id),
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'reference_type' => ReturnOrder::class,
            'reference_id' => $returnOrder->id,
            'notes' => "ارتجاع من المندوب {$returnOrder->return_no}",
            'status' => 'posted',
            'created_by' => $employee?->id,
        ]);

        foreach ($returnOrder->items as $item) {
            $unitId = $item->item_unit_id;
            if (!$unitId) {
                $unitId = $item->item?->base_unit_id;
            }
            if (!$unitId) {
                $unitId = \App\Models\Unit::first()?->id;
            }
            $conversionFactor = $unitService->getConversionFactor($item->item_id, $unitId);
            $qtyInBase = $unitService->toBase($item->item_id, $unitId, $item->returned_quantity);
            InventoryTransactionItem::create([
                'inventory_transaction_id' => $txn->id,
                'item_id' => $item->item_id,
                'unit_id' => $unitId,
                'conversion_factor' => $conversionFactor,
                'qty' => $qtyInBase,
                'unit_cost' => $item->sales_price,
                'total_cost' => $item->line_total,
            ]);
        }

        RepItemDistribution::where('employee_id', $returnOrder->employee_id)
            ->whereNull('return_order_id')
            ->where('status', 'closed')
            ->whereHas('issueOrder', function ($q) use ($returnOrder) {
                $q->where('employee_id', $returnOrder->employee_id);
            })
            ->update(['return_order_id' => $returnOrder->id]);

        $customer = Customer::where('company_id', $user->company_id)->first();

        $salesInvoice = SalesInvoice::create([
            'company_id' => $returnOrder->company_id,
            'warehouse_id' => $returnOrder->warehouse_id,
            'customer_id' => $customer?->id,
            'sales_rep_id' => $returnOrder->employee_id,
            'invoice_date' => now()->toDateString(),
            'invoice_time' => now()->format('H:i:s'),
            'subtotal' => 0,
            'item_discount_total' => 0,
            'invoice_discount_total' => 0,
            'tax_total' => 0,
            'incentive_total' => 0,
            'net_total' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'status' => 'approved',
            'notes' => "أمر بيع من اغلاق الإذن {$returnOrder->return_no}",
            'created_by' => $employee?->id,
            'approved_by' => $employee?->id,
        ]);

        $subtotal = 0;
        foreach ($returnOrder->items as $item) {
            $lineTotal = $item->line_total;
            $subtotal += $lineTotal;

            SalesInvoiceItem::create([
                'sales_invoice_id' => $salesInvoice->id,
                'item_id' => $item->item_id,
                'unit_id' => $item->item_unit_id,
                'warehouse_id' => $returnOrder->warehouse_id,
                'qty' => $item->returned_quantity,
                'bonus_qty' => 0,
                'price' => $item->sales_price,
                'gross_amount' => $lineTotal,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_percent' => 0,
                'tax_amount' => 0,
                'net_amount' => $lineTotal,
            ]);
        }

        $tax = $subtotal * 0.15;
        $salesInvoice->update([
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'net_total' => $subtotal + $tax,
            'remaining_amount' => $subtotal + $tax,
        ]);

        return [
            'return_order' => $returnOrder->fresh(),
            'sales_invoice' => $salesInvoice->fresh(),
        ];
    });

    return response()->json([
        'message' => 'تمت الموافقة على الارتجاع وإنشاء أمر البيع',
        'data' => $result,
    ]);
});

RouteFacade::get('handheld/my-issue-orders', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $employee = resolveEmployee($request);
    if (!$employee) {
        $representative = \App\Models\Representative::where('user_id', $user->id)->first();
        if ($representative) {
            $employee = \App\Models\Employee::where('national_id', $representative->code)->first();
        }
    }

    if ($employee) {
        $orders = \App\Models\IssueOrder::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'issued'])
            ->whereNull('received_by')
            ->with(['items.item', 'items.unit', 'warehouse', 'salesTerritory'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        foreach ($orders as $order) {
            $vl = \App\Models\VehicleLoad::where('issue_order_id', $order->id)->first();
            $order->load_no = $vl?->load_no ?? '';
            $order->sales_territory_name = $order->salesTerritory?->name_ar;
        }

        if ($orders->isNotEmpty()) {
            return response()->json(['data' => $orders]);
        }
    }

    $orders = \App\Models\IssueOrder::where('company_id', $user->company_id)
        ->whereIn('status', ['approved', 'issued'])
        ->whereNull('received_by')
        ->with(['items.item', 'items.unit', 'warehouse', 'salesTerritory'])
        ->orderByDesc('id')
        ->limit(20)
        ->get();

    foreach ($orders as $order) {
        $vl = \App\Models\VehicleLoad::where('issue_order_id', $order->id)->first();
        $order->load_no = $vl?->load_no ?? '';
        $order->sales_territory_name = $order->salesTerritory?->name_ar;
    }

    return response()->json(['data' => $orders]);
});

RouteFacade::get('handheld/products', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $search = $request->input('search', '');

    $query = Item::where('is_active', true)
        ->where('company_id', $user->company_id)
        ->with(['productCompany', 'itemCategory', 'itemUnits.unit']);

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%$search%")
              ->orWhere('name_en', 'like', "%$search%")
              ->orWhere('code', 'like', "%$search%")
              ->orWhere('barcode', 'like', "%$search%");
        });
    }

    $items = $query->orderBy('name_ar')->paginate($request->per_page ?? 100);

    return response()->json([
        'data' => $items->items(),
        'total' => $items->total(),
    ]);
});

RouteFacade::get('handheld/customers', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $today = $request->input('day', now()->format('l'));
    $todayNumber = DayOfWeekHelper::nameToNumber($today) ?? DayOfWeekHelper::todayNumber();

    $employee = resolveEmployee($request);
    $delegatePhone = $employee?->mobile ?? $employee?->phone ?? null;

    $supervisorPhone = null;
    if ($employee) {
        $salesmanAssignment = DB::table('salesman_assignments')
            ->where('employee_id', $employee->id)
            ->where('job_role', 'salesman')
            ->where('is_active', true)
            ->first();

        if ($salesmanAssignment && $salesmanAssignment->parent_assignment_id) {
            $supervisorAssignment = DB::table('salesman_assignments')
                ->where('id', $salesmanAssignment->parent_assignment_id)
                ->first();
            if ($supervisorAssignment) {
                $supervisor = Employee::where('id', $supervisorAssignment->employee_id)->first();
                $supervisorPhone = $supervisor?->mobile ?? $supervisor?->phone ?? null;
            }
        }
    }

    $employeeRouteIds = collect();
    if ($employee) {
        $scheduleRouteIds = RouteSchedule::where('is_active', true)
            ->where('employee_id', $employee->id)
            ->pluck('route_id');
        $assignmentRouteIds = RouteAssignment::where('driver_id', $employee->id)
            ->whereDate('assignment_date', now()->toDateString())
            ->where('is_active', true)
            ->pluck('route_id');
        $employeeRouteIds = $scheduleRouteIds->merge($assignmentRouteIds)->unique();
    }

    $routeCustomers = RouteCustomer::where('is_active', true)
        ->whereHas('customer', fn($q) => $q->where('company_id', $user->company_id));

    if ($employeeRouteIds->isNotEmpty()) {
        $routeCustomers = $routeCustomers->whereIn('route_id', $employeeRouteIds);
    }

    $routeCustomers = $routeCustomers
        ->with(['customer', 'route', 'route.salesTerritory'])
        ->orderBy('visit_order')
        ->get()
        ->filter(fn($rc) => $rc->customer && !$rc->customer->trashed());

    if ($routeCustomers->isEmpty()) {
        $customers = Customer::where('is_active', true)
            ->where('company_id', $user->company_id)
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'data' => $customers->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name_ar' => $c->name_ar,
                'name_en' => $c->name_en,
                'phone' => $c->phone,
                'mobile' => $c->mobile,
                'tax_number' => $c->tax_number ?: $c->national_id,
                'national_id' => $c->national_id,
                'address_line' => $c->address_line,
                'governorate' => $c->governorate?->name_ar,
                'city' => $c->city?->name_ar,
                'area' => $c->area?->name_ar,
                'balance' => calculateCustomerBalance($c->id, $user->company_id),
                'visit_order' => null,
                'visit_frequency' => null,
                'day_of_week' => null,
                'is_today' => true,
                'is_active' => true,
                'delegate_phone' => $delegatePhone,
                'supervisor_phone' => $supervisorPhone,
                'sales_territory_id' => null,
                'sales_territory_name' => null,
            ])->values(),
            'today_count' => $customers->count(),
            'other_count' => 0,
            'today' => $today,
        ]);
    }

    $todayRouteIds = RouteSchedule::where('is_active', true)
        ->whereRaw("INSTR(',' || day_of_week || ',', ',' || ? || ',') > 0", [$todayNumber]);
    if ($employeeRouteIds->isNotEmpty()) {
        $todayRouteIds = $todayRouteIds->whereIn('route_id', $employeeRouteIds);
    }
    $todayRouteIds = $todayRouteIds->pluck('route_id');

    $data = [];
    foreach ($routeCustomers as $rc) {
        $isToday = $todayRouteIds->contains($rc->route_id);
        $schedule = RouteSchedule::where('route_id', $rc->route_id)->where('is_active', true)->first();

        $data[] = [
            'id' => $rc->customer->id,
            'code' => $rc->customer->code,
            'name_ar' => $rc->customer->name_ar,
            'name_en' => $rc->customer->name_en,
            'phone' => $rc->customer->phone,
            'mobile' => $rc->customer->mobile,
            'tax_number' => $rc->customer->tax_number ?: $rc->customer->national_id,
            'national_id' => $rc->customer->national_id,
            'address_line' => $rc->customer->address_line,
            'governorate' => $rc->customer->governorate?->name_ar,
            'city' => $rc->customer->city?->name_ar,
            'area' => $rc->customer->area?->name_ar,
            'visit_order' => $rc->visit_order,
            'visit_frequency' => $rc->visit_frequency,
            'day_of_week' => $schedule?->day_of_week,
            'route_id' => $rc->route_id,
            'is_today' => $isToday,
            'is_active' => $rc->is_active,
            'balance' => calculateCustomerBalance($rc->customer->id, $user->company_id),
            'delegate_phone' => $delegatePhone,
            'supervisor_phone' => $supervisorPhone,
            'sales_territory_id' => $rc->route?->sales_territory_id,
            'sales_territory_name' => $rc->route?->salesTerritory?->name_ar,
        ];
    }

    $todayCount = count(array_filter($data, fn($d) => $d['is_today']));
    $otherCount = count($data) - $todayCount;

    return response()->json([
        'data' => $data,
        'today_count' => $todayCount,
        'other_count' => $otherCount,
        'today' => $today,
    ]);
});

RouteFacade::post('handheld/register-device', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'device_code' => 'required|string|max:20',
        'device_name' => 'nullable|string|max:100',
        'device_model' => 'nullable|string|max:100',
        'os_version' => 'nullable|string|max:50',
    ]);

    $employee = resolveEmployee($request);

    $device = Device::firstOrCreate(
        ['device_code' => $request->device_code],
        [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'sales_rep_id' => $employee->id,
            'company_id' => $user->company_id,
            'device_name' => $request->device_name,
            'device_model' => $request->device_model,
            'os_version' => $request->os_version,
            'is_active' => true,
        ]
    );

    $device->update([
        'sales_rep_id' => $employee->id,
        'last_sync_at' => now(),
    ]);

    return response()->json([
        'data' => [
            'device_id' => $device->id,
            'device_code' => $device->device_code,
            'last_sequence' => $device->last_sequence,
        ],
    ]);
});

RouteFacade::post('handheld/create-invoice', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.qty' => 'required|numeric|min:1',
        'items.*.price' => 'required|numeric|min:0',
        'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        'items.*.unit_id' => 'nullable|exists:units,id',
        'items.*.issue_order_id' => 'nullable|exists:issue_orders,id',
        'device_id' => 'nullable|exists:devices,id',
        'temp_invoice_no' => 'nullable|string|max:50',
        'invoice_no' => 'nullable|string|max:50',
        'branch_id' => 'nullable|exists:branches,id',
        'paid_amount' => 'nullable|numeric|min:0',
        'cash_received' => 'nullable|numeric|min:0',
        'skip_treasury' => 'nullable|boolean',
        'mode' => 'nullable|string|in:online,offline',
        'sales_territory_id' => 'nullable|exists:sales_territories,id',
    ]);

    $employee = resolveEmployee($request);
    if (!$employee) {
        return response()->json(['message' => 'الموظف غير موجود'], 404);
    }

    $result = DB::transaction(function () use ($request, $user, $employee) {
        $subtotal = 0;
        $taxTotal = 0;

        $itemsData = [];
        foreach ($request->items as $item) {
            $lineTotal = $item['qty'] * $item['price'];
            $taxPercent = $item['tax_percent'] ?? 0;
            $taxAmount = $lineTotal * ($taxPercent / 100);
            $subtotal += $lineTotal;
            $taxTotal += $taxAmount;

            $itemsData[] = [
                'item_id' => $item['item_id'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'gross_amount' => $lineTotal,
                'net_amount' => $lineTotal + $taxAmount,
                'unit_id' => $item['unit_id'] ?? null,
                'issue_order_id' => $item['issue_order_id'] ?? null,
            ];
        }

        $netTotal = $subtotal + $taxTotal;
        $paidAmount = $request->input('paid_amount', $netTotal);
        $cashReceived = $request->input('cash_received', $paidAmount);
        $skipTreasury = $request->input('skip_treasury', false);
        $isBalancePayment = $cashReceived < $paidAmount;
        $effectivePaid = $isBalancePayment ? $cashReceived : $paidAmount;
        $remainingAmount = $isBalancePayment ? 0 : max(0, $netTotal - $paidAmount);

        $now = now();

        $invoiceNo = $request->input('invoice_no');
        if (empty($invoiceNo)) {
            return response()->json(['message' => 'invoice_no مطلوب من التطبيق'], 422);
        }

        $warehouse = \App\Models\Warehouse::where('company_id', $user->company_id)
            ->where('is_active', true)->first();

        $invoice = SalesInvoice::create([
            'company_id' => $user->company_id,
            'branch_id' => $request->branch_id,
            'warehouse_id' => $warehouse?->id,
            'invoice_no' => $invoiceNo,
            'temp_invoice_no' => $request->temp_invoice_no,
            'source' => 'mobile',
            'mode' => $request->input('mode', 'online'),
            'device_id' => $request->device_id,
            'sync_status' => 'synced',
            'synced_at' => now(),
            'customer_id' => $request->customer_id,
            'sales_rep_id' => $employee->id,
            'sales_territory_id' => $request->input('sales_territory_id'),
            'issue_order_id' => $itemsData[0]['issue_order_id'] ?? null,
            'invoice_date' => now()->toDateString(),
            'invoice_time' => now()->format('H:i:s'),
            'subtotal' => $subtotal,
            'item_discount_total' => 0,
            'invoice_discount_total' => 0,
            'tax_total' => $taxTotal,
            'incentive_total' => 0,
            'net_total' => $netTotal,
            'paid_amount' => $effectivePaid,
            'remaining_amount' => $remainingAmount,
            'status' => 'approved',
            'notes' => $isBalancePayment
                ? 'فاتورة مبيعات - سداد من رصيد سابق'
                : 'فاتورة كاش من جهاز المندوب',
            'created_by' => $employee->id,
        ]);

        foreach ($itemsData as $itemData) {
            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_id' => $itemData['item_id'],
                'unit_id' => $itemData['unit_id'] ?? null,
                'qty' => $itemData['qty'],
                'bonus_qty' => 0,
                'price' => $itemData['price'],
                'gross_amount' => $itemData['gross_amount'],
                'discount_type' => null,
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_percent' => $itemData['tax_percent'],
                'tax_amount' => $itemData['tax_amount'],
                'net_amount' => $itemData['net_amount'],
            ]);
        }

        if ($request->input('skip_treasury', false)) {
            $invoice->update([
                'paid_amount' => $effectivePaid,
                'remaining_amount' => $remainingAmount,
                'status' => 'posted',
                'posted_at' => now(),
            ]);
        } else {
            $invoice->post();
        }

        foreach ($itemsData as $itemData) {
            $distribution = RepItemDistribution::where('company_id', $user->company_id)
                ->where('employee_id', $employee->id)
                ->where('item_id', $itemData['item_id'])
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if ($distribution) {
                $distribution->update([
                    'sold_qty' => $distribution->sold_qty + $itemData['qty'],
                    'remaining_qty' => max(0, $distribution->remaining_qty - $itemData['qty']),
                ]);
            }
        }

        if ($request->device_id) {
            Device::where('id', $request->device_id)->update(['last_sync_at' => now()]);
        }

        return $invoice;
    });

    return response()->json([
        'message' => 'تم إنشاء الفاتورة بنجاح',
        'data' => $result->load('items.item'),
    ], 201);
});

RouteFacade::post('handheld/sync-invoices', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'invoices' => 'required|array|min:1',
        'branch_id' => 'nullable|integer',
        'invoices.*.uuid' => 'required|string',
        'invoices.*.customer_id' => 'required|exists:customers,id',
        'invoices.*.temp_invoice_no' => 'nullable|string',
        'invoices.*.invoice_no' => 'nullable|string',
        'invoices.*.device_id' => 'nullable|integer',
        'invoices.*.invoice_date' => 'required|date',
        'invoices.*.items' => 'required|array|min:1',
        'invoices.*.items.*.item_id' => 'required|exists:items,id',
        'invoices.*.items.*.qty' => 'required|numeric|min:1',
        'invoices.*.items.*.price' => 'required|numeric|min:0',
        'invoices.*.items.*.unit_id' => 'nullable|integer',
        'invoices.*.items.*.issue_order_id' => 'nullable|integer',
        'invoices.*.branch_id' => 'nullable|integer',
        'invoices.*.paid_amount' => 'nullable|numeric|min:0',
        'invoices.*.cash_received' => 'nullable|numeric|min:0',
        'invoices.*.sales_territory_id' => 'nullable|integer',
    ]);

    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'الموظف غير موجود في النظام'], 404);
    }

    $results = [];

    foreach ($request->invoices as $invoiceData) {
        $existing = SalesInvoice::where('uuid', $invoiceData['uuid'])->first();
        if ($existing) {
            $results[] = [
                'uuid' => $invoiceData['uuid'],
                'status' => 'already_synced',
                'invoice_no' => $existing->invoice_no,
            ];
            continue;
        }

        $invoice = DB::transaction(function () use ($invoiceData, $user, $employee, $request) {
            $subtotal = 0;
            $taxTotal = 0;
            $itemsData = [];

            foreach ($invoiceData['items'] as $item) {
                $lineTotal = $item['qty'] * $item['price'];
                $taxPercent = $item['tax_percent'] ?? 0;
                $taxAmount = $lineTotal * ($taxPercent / 100);
                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;
                $itemsData[] = [
                    'item_id' => $item['item_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'gross_amount' => $lineTotal,
                    'net_amount' => $lineTotal + $taxAmount,
                    'unit_id' => $item['unit_id'] ?? null,
                    'issue_order_id' => $item['issue_order_id'] ?? null,
                ];
            }

        $netTotal = $subtotal + $taxTotal;
        $paidAmount = $invoiceData['paid_amount'] ?? $netTotal;
        $cashReceived = $invoiceData['cash_received'] ?? $paidAmount;
        $remainingAmount = max(0, $netTotal - $paidAmount);

            $now = now();

            $invoiceNo = $invoiceData['invoice_no'] ?? null;
            if (empty($invoiceNo)) {
                return response()->json(['message' => 'invoice_no مطلوب من التطبيق'], 422);
            }

            $warehouse = \App\Models\Warehouse::where('company_id', $user->company_id)
                ->where('is_active', true)->first();

            $inv = SalesInvoice::create([
                'company_id' => $user->company_id,
                'branch_id' => $invoiceData['branch_id'] ?? $request->input('branch_id'),
                'warehouse_id' => $warehouse?->id,
                'invoice_no' => $invoiceNo,
                'temp_invoice_no' => $invoiceData['temp_invoice_no'] ?? null,
                'uuid' => $invoiceData['uuid'],
                'source' => 'mobile',
                'mode' => 'offline',
                'device_id' => $invoiceData['device_id'] ?? null,
                'sync_status' => 'synced',
                'synced_at' => now(),
                'customer_id' => $invoiceData['customer_id'],
                'sales_rep_id' => $employee->id,
                'sales_territory_id' => $invoiceData['sales_territory_id'] ?? null,
                'issue_order_id' => $itemsData[0]['issue_order_id'] ?? null,
                'invoice_date' => $invoiceData['invoice_date'],
                'invoice_time' => now()->format('H:i:s'),
                'subtotal' => $subtotal,
                'item_discount_total' => 0,
                'invoice_discount_total' => 0,
                'tax_total' => $taxTotal,
                'incentive_total' => 0,
                'net_total' => $netTotal,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => 'approved',
                'notes' => $cashReceived < $paidAmount
                    ? 'فاتورة مبيعات - سداد من رصيد سابق'
                    : 'فاتورة كاش اوفلاين - تم المزامنة',
                'created_by' => $employee->id,
            ]);

            foreach ($itemsData as $itemData) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'item_id' => $itemData['item_id'],
                    'unit_id' => $itemData['unit_id'] ?? null,
                    'qty' => $itemData['qty'],
                    'bonus_qty' => 0,
                    'price' => $itemData['price'],
                    'gross_amount' => $itemData['gross_amount'],
                    'discount_type' => null,
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => $itemData['tax_percent'],
                    'tax_amount' => $itemData['tax_amount'],
                    'net_amount' => $itemData['net_amount'],
                ]);
            }

            if ($cashReceived < $paidAmount) {
                $inv->update(['paid_amount' => $cashReceived]);
                try { $inv->post(); } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('sync-invoices post failed', ['invoice_id' => $inv->id, 'error' => $e->getMessage()]);
                }
                $inv->update([
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                ]);
            } else {
                try { $inv->post(); } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('sync-invoices post failed', ['invoice_id' => $inv->id, 'error' => $e->getMessage()]);
                }
            }

            foreach ($itemsData as $itemData) {
                $distribution = RepItemDistribution::where('company_id', $user->company_id)
                    ->where('employee_id', $employee->id)
                    ->where('item_id', $itemData['item_id'])
                    ->where('status', 'active')
                    ->latest('id')
                    ->first();

                if ($distribution) {
                    $distribution->update([
                        'sold_qty' => $distribution->sold_qty + $itemData['qty'],
                        'remaining_qty' => max(0, $distribution->remaining_qty - $itemData['qty']),
                    ]);
                }
            }

            if (!empty($invoiceData['device_id'])) {
                Device::where('id', $invoiceData['device_id'])->update(['last_sync_at' => now()]);
            }

            return $inv;
        });

        $results[] = [
            'uuid' => $invoiceData['uuid'],
            'status' => 'synced',
            'invoice_no' => $invoice->invoice_no,
        ];
    }

    return response()->json(['message' => 'تمت المزامنة', 'data' => $results]);
});

RouteFacade::post('handheld/end-visit', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'visit_status' => 'required|string|in:S,V,C',
        'visit_reason' => 'nullable|string|max:255',
    ]);

    $employee = resolveEmployee($request);

    $visitStatus = $request->input('visit_status');
    $visitReason = $request->input('visit_reason');

    $visit = CustomerVisit::where('employee_id', $employee->id)
        ->where('customer_id', $request->customer_id)
        ->whereDate('visit_date', now()->toDateString())
        ->where('visit_status', 'pending')
        ->latest()
        ->first();

    if ($visit) {
        $visit->update([
            'visit_status' => $visitStatus,
            'visit_reason' => $visitReason,
            'check_out_time' => now(),
        ]);
    } else {
        CustomerVisit::create([
            'employee_id' => $employee->id,
            'customer_id' => $request->customer_id,
            'visit_date' => now()->toDateString(),
            'check_in_time' => now()->format('H:i:s'),
            'check_out_time' => now()->format('H:i:s'),
            'visit_status' => $visitStatus,
            'visit_reason' => $visitReason,
        ]);
    }

    return response()->json(['message' => 'تم إنهاء الزيارة', 'visit_status' => $visitStatus]);
});

RouteFacade::get('handheld/my-invoices', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    if ($employee) {
        $invoices = SalesInvoice::where('sales_rep_id', $employee->id)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->with(['customer', 'items.item'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        if ($invoices->isNotEmpty()) {
            return response()->json(['data' => $invoices]);
        }
    }

    $invoices = SalesInvoice::where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->with(['customer', 'items.item'])
        ->orderByDesc('invoice_date')
        ->orderByDesc('id')
        ->limit(100)
        ->get();

    return response()->json(['data' => $invoices]);
});

RouteFacade::get('handheld/customer-invoices/{customerId}', function (\Illuminate\Http\Request $request, $customerId) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    $invoices = SalesInvoice::where('customer_id', $customerId)
        ->where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->with(['items.item', 'items.unit'])
        ->orderByDesc('invoice_date')
        ->orderByDesc('id')
        ->limit(50)
        ->get();

    return response()->json(['data' => $invoices]);
});

RouteFacade::get('handheld/customers-last-invoices', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $customerIds = DB::table('customers')
        ->where('company_id', $user->company_id)
        ->where('is_active', 1)
        ->pluck('id');

    $results = new \stdClass();
    foreach ($customerIds as $custId) {
        $invoiceIds = SalesInvoice::where('customer_id', $custId)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(4)
            ->pluck('id');

        if ($invoiceIds->isEmpty()) continue;

        $invoices = SalesInvoice::whereIn('id', $invoiceIds)
            ->with(['items.item'])
            ->get();

        $results->{(string) $custId} = $invoices->map(function ($inv) {
            return [
                'id' => $inv->id,
                'uuid' => $inv->uuid,
                'invoice_no' => $inv->invoice_no,
                'invoice_date' => $inv->invoice_date,
                'subtotal' => $inv->subtotal,
                'tax_total' => $inv->tax_total,
                'net_total' => $inv->net_total,
                'paid_amount' => $inv->paid_amount,
                'remaining_amount' => $inv->remaining_amount,
                'status' => $inv->status,
                'items' => $inv->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_id' => $item->item_id,
                        'item_name' => $item->item->name_ar ?? '',
                        'quantity' => $item->qty,
                        'unit_price' => $item->price,
                        'total' => $item->net_amount,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }

    return response()->json(['data' => $results]);
});

RouteFacade::get('handheld/distribution-plan-today', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $employee = DB::table('employees')
        ->where('email', $user->email)
        ->first();

    if (!$employee) {
        $representative = Representative::where('user_id', $user->id)->first();
        if ($representative) {
            $employee = DB::table('employees')
                ->where('national_id', $representative->code)
                ->first();
        }
    }

    if (!$employee) {
        return response()->json(['data' => []]);
    }

    $today = now()->toDateString();

    $activePlan = DB::table('distribution_plans')
        ->where('company_id', $user->company_id)
        ->whereIn('status', ['approved', 'applied'])
        ->whereDate('plan_date', '<=', $today)
        ->whereNull('deleted_at')
        ->orderByDesc('plan_date')
        ->orderByDesc('id')
        ->first();

    if (!$activePlan) {
        return response()->json(['data' => []]);
    }

    $repPlan = DB::table('distribution_plan_reps')
        ->where('distribution_plan_id', $activePlan->id)
        ->where('sales_rep_id', $employee->id)
        ->first();

    if (!$repPlan) {
        $repPlan = DB::table('distribution_plan_reps')
            ->where('distribution_plan_id', $activePlan->id)
            ->first();
    }

    if (!$repPlan) {
        return response()->json(['data' => []]);
    }

    $customerPlans = DB::table('distribution_plan_customers')
        ->where('distribution_plan_rep_id', $repPlan->id)
        ->get();

    $result = [];
    foreach ($customerPlans as $cp) {
        $items = DB::table('distribution_plan_items')
            ->where('distribution_plan_customer_id', $cp->id)
            ->get()
            ->map(function ($pi) {
                $item = DB::table('items')->where('id', $pi->item_id)->first();
                return [
                    'item_id' => $pi->item_id,
                    'item_name' => $item->name_ar ?? '',
                    'historical_avg' => $pi->historical_avg,
                    'historical_ratio' => $pi->historical_ratio,
                    'allocated_qty' => $pi->allocated_qty,
                    'final_qty' => $pi->final_qty,
                ];
            })
            ->toArray();

        $customer = DB::table('customers')->where('id', $cp->customer_id)->first();

        $result[] = [
            'customer_id' => $cp->customer_id,
            'customer_name' => $customer->name_ar ?? '',
            'avg_monthly_sales' => $cp->avg_monthly_sales,
            'customer_weight' => $cp->customer_weight,
            'allocated_qty' => $cp->allocated_qty,
            'final_qty' => $cp->final_qty,
            'items' => $items,
        ];
    }

    return response()->json([
        'data' => $result,
        'plan_no' => $activePlan->plan_no,
        'plan_date' => $activePlan->plan_date,
        'total_quantity' => $activePlan->total_quantity,
        'units_per_carton' => $activePlan->units_per_carton ?? 50,
        'enforce_plan_limit' => (bool) ($activePlan->enforce_plan_limit ?? false),
    ]);
});

RouteFacade::post('handheld/clear-all-data', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $tables = [
        'promotion_execution_logs',
        'e_invoice_transactions',
        'customer_returns',
        'collections',
        'sales_invoice_incentives',
        'sales_invoice_taxes',
        'sales_invoice_discounts',
        'sales_invoice_items',
        'sales_invoices',
        'purchase_expenses',
        'purchase_returns',
        'purchase_invoice_items',
        'purchase_invoices',
        'vehicle_load_items',
        'vehicle_loads',
        'vehicle_loadings',
        'distribution_plan_items',
        'distribution_plan_customers',
        'distribution_plan_reps',
        'distribution_plan_products',
        'distribution_plans',
        'load_request_items',
        'issue_orders',
        'return_orders',
        'salesman_settlements',
        'load_requests',
    ];

    DB::statement('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $table) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            DB::table($table)->truncate();
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    return response()->json(['message' => 'تم حذف جميع البيانات بنجاح']);
});

RouteFacade::get('handheld/salesmen', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $users = \App\Models\User::where('is_active', true)
        ->where('company_id', $user->company_id)
        ->where('usercode', '!=', 99999)
        ->whereHas('roles', fn($q) => $q->where('name', 'SalesMan'))
        ->select('id', 'usercode', 'name', 'email', 'phone', 'company_id', 'password')
        ->get()
        ->map(function ($u) {
            $defaultBranch = $u->branches()->wherePivot('is_default', true)->first();
            return [
                'id' => $u->id,
                'usercode' => $u->usercode,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'company_id' => $u->company_id,
                'password' => $u->password,
                'branch_id' => $defaultBranch?->id,
            ];
        });

    return response()->json(['data' => $users]);
});

RouteFacade::get('handheld/distributions', [\App\Http\Controllers\Api\Sales\RepItemDistributionController::class, 'index']);
RouteFacade::post('handheld/distributions', [\App\Http\Controllers\Api\Sales\RepItemDistributionController::class, 'store']);
RouteFacade::post('handheld/distributions/bulk', [\App\Http\Controllers\Api\Sales\RepItemDistributionController::class, 'bulkStore']);
RouteFacade::post('handheld/return-orders/{id}/link-distribution', [\App\Http\Controllers\Api\Sales\RepItemDistributionController::class, 'linkReturnOrder']);

RouteFacade::get('handheld/customer-info/{customerId}', function (\Illuminate\Http\Request $request, $customerId) {
    $user = $request->user();

    $employee = resolveEmployee($request);
    $delegatePhone = $employee?->mobile ?? $employee?->phone ?? null;

    $supervisorPhone = null;
    if ($employee) {
        $salesmanAssignment = DB::table('salesman_assignments')
            ->where('employee_id', $employee->id)
            ->where('job_role', 'salesman')
            ->where('is_active', true)
            ->first();

        if ($salesmanAssignment && $salesmanAssignment->parent_assignment_id) {
            $supervisorAssignment = DB::table('salesman_assignments')
                ->where('id', $salesmanAssignment->parent_assignment_id)
                ->first();
            if ($supervisorAssignment) {
                $supervisor = Employee::where('id', $supervisorAssignment->employee_id)->first();
                $supervisorPhone = $supervisor?->mobile ?? $supervisor?->phone ?? null;
            }
        }
    }

    $customer = \App\Models\Customer::where('id', $customerId)
        ->where('company_id', $user->company_id)
        ->first();

    if (!$customer) {
        return response()->json(['message' => 'العميل غير موجود'], 404);
    }

    $invoices = SalesInvoice::where('customer_id', $customerId)
        ->where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->with(['items.item', 'salesTerritory'])
        ->orderByDesc('invoice_date')
        ->orderByDesc('id')
        ->limit(10)
        ->get()
        ->map(function ($inv) {
            return [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'invoice_date' => $inv->invoice_date,
                'subtotal' => (float) $inv->subtotal,
                'tax_total' => (float) $inv->tax_total,
                'net_total' => (float) $inv->net_total,
                'paid_amount' => (float) $inv->paid_amount,
                'remaining_amount' => (float) $inv->remaining_amount,
                'status' => $inv->status,
                'sales_territory_id' => $inv->sales_territory_id,
                'sales_territory_name' => $inv->salesTerritory?->name_ar,
                'items' => $inv->items->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'item_name' => $item->item->name_ar ?? '',
                        'qty' => $item->qty,
                        'price' => (float) $item->price,
                        'net_amount' => (float) $item->net_amount,
                    ];
                })->values()->toArray(),
            ];
        });

    $balance = calculateCustomerBalance($customerId, $user->company_id);

    $allInvoices = SalesInvoice::where('customer_id', $customerId)
        ->where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->selectRaw('COALESCE(SUM(net_total), 0) as total_invoiced, COALESCE(SUM(paid_amount), 0) as total_paid')
        ->first();

    return response()->json([
        'data' => [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name_ar' => $customer->name_ar,
                'name_en' => $customer->name_en,
                'phone' => $customer->phone,
                'mobile' => $customer->mobile,
                'tax_number' => $customer->tax_number ?: $customer->national_id,
                'national_id' => $customer->national_id,
                'address_line' => $customer->address_line,
                'governorate' => $customer->governorate?->name_ar,
                'city' => $customer->city?->name_ar,
                'area' => $customer->area?->name_ar,
                'credit_limit' => (float) ($customer->credit_limit ?? 0),
                'delegate_phone' => $delegatePhone,
                'supervisor_phone' => $supervisorPhone,
            ],
            'invoices' => $invoices,
            'balance' => $balance,
            'total_invoiced' => (float) $allInvoices->total_invoiced,
            'total_paid' => (float) $allInvoices->total_paid,
        ],
    ]);
});

RouteFacade::post('handheld/pay-from-balance', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'amount' => 'required|numeric|min:0.01',
    ]);

    $uuid = $request->input('uuid');
    if ($uuid) {
        $existing = \App\Models\Sales\Collection::where('company_id', $user->company_id)
            ->where('notes', 'like', "%uuid:$uuid%")
            ->first();
        if ($existing) {
            return response()->json(['message' => 'تم بالفعل', 'data' => ['collection_id' => $existing->id]], 200);
        }
    }

    $employee = resolveEmployee($request);

    $customer = \App\Models\Customer::where('id', $request->customer_id)
        ->where('company_id', $user->company_id)
        ->first();

    if (!$customer) {
        return response()->json(['message' => 'العميل غير موجود'], 404);
    }

    $balance = calculateCustomerBalance($request->customer_id, $user->company_id);

    if ($request->amount > $balance) {
        return response()->json(['message' => 'المبلغ أكبر من الرصيد المتاح'], 422);
    }

    $notes = 'الدفع من رصيد سابق';
    if ($uuid) $notes .= " uuid:$uuid";
    $collection = \App\Models\Sales\Collection::create([
        'company_id' => $user->company_id,
        'branch_id' => $request->input('branch_id'),
        'collection_date' => now()->toDateString(),
        'collection_time' => now()->format('H:i:s'),
        'sales_rep_id' => $employee?->id,
        'customer_id' => $request->customer_id,
        'amount' => $request->amount,
        'notes' => $notes,
        'status' => 'approved',
        'created_by' => $employee?->id,
    ]);

    return response()->json([
        'message' => 'تم تسجيل السداد من الرصيد السابق بنجاح',
        'data' => [
            'collection_id' => $collection->id,
            'collection_no' => $collection->collection_no,
            'amount' => (float) $collection->amount,
            'remaining_balance' => round($balance - $request->amount, 2),
        ],
    ], 201);
});

RouteFacade::post('handheld/add-customer-credit', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'amount' => 'required|numeric|min:0.01',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    $employee = resolveEmployee($request);

    $customer = \App\Models\Customer::where('id', $request->customer_id)
        ->where('company_id', $user->company_id)
        ->first();

    if (!$customer) {
        return response()->json(['message' => 'العميل غير موجود'], 404);
    }

    // Create a negative collection (credit) to increase customer balance
    $collection = \App\Models\Sales\Collection::create([
        'company_id' => $user->company_id,
        'branch_id' => $request->input('branch_id'),
        'collection_date' => now()->toDateString(),
        'collection_time' => now()->format('H:i:s'),
        'sales_rep_id' => $employee?->id,
        'customer_id' => $request->customer_id,
        'amount' => -$request->amount, // Negative = credit to customer
        'notes' => 'إضافة رصيد (دفعة أكثر من الفاتورة)',
        'status' => 'approved',
        'created_by' => $employee?->id,
    ]);

    // Calculate new balance
    $newBalance = calculateCustomerBalance($request->customer_id, $user->company_id);

    return response()->json([
        'message' => 'تم إضافة الرصيد بنجاح',
        'data' => [
            'collection_id' => $collection->id,
            'amount' => (float) $request->amount,
            'new_balance' => round($newBalance, 2),
        ],
    ], 201);
});

RouteFacade::post('handheld/sync-visits', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'visits' => 'required|array|min:1',
        'visits.*.uuid' => 'required|string',
        'visits.*.customer_id' => 'required|exists:customers,id',
        'visits.*.visit_date' => 'required|date',
        'visits.*.check_in_time' => 'nullable|string',
        'visits.*.check_out_time' => 'nullable|string',
        'visits.*.visit_status' => 'required|string|in:S,V,C',
        'visits.*.visit_reason' => 'nullable|string|max:255',
        'visits.*.latitude' => 'nullable|numeric',
        'visits.*.longitude' => 'nullable|numeric',
        'visits.*.notes' => 'nullable|string',
        'visits.*.route_id' => 'nullable|exists:routes,id',
    ]);

    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'الموظف غير موجود في النظام'], 404);
    }

    $results = [];

    foreach ($request->visits as $visitData) {
        $existing = CustomerVisit::where('employee_id', $employee->id)
            ->where('customer_id', $visitData['customer_id'])
            ->whereDate('visit_date', $visitData['visit_date'])
            ->where('visit_status', '!=', 'pending')
            ->first();

        if ($existing) {
            $results[] = [
                'uuid' => $visitData['uuid'],
                'status' => 'already_synced',
                'visit_id' => $existing->id,
            ];
            continue;
        }

        $pendingVisit = CustomerVisit::where('employee_id', $employee->id)
            ->where('customer_id', $visitData['customer_id'])
            ->whereDate('visit_date', $visitData['visit_date'])
            ->where('visit_status', 'pending')
            ->latest()
            ->first();

        if ($pendingVisit) {
            $pendingVisit->update([
                'visit_status' => $visitData['visit_status'],
                'visit_reason' => $visitData['visit_reason'] ?? null,
                'check_out_time' => !empty($visitData['check_out_time']) ? $visitData['check_out_time'] : now()->format('H:i:s'),
                'latitude' => $visitData['latitude'] ?? null,
                'longitude' => $visitData['longitude'] ?? null,
                'notes' => $visitData['notes'] ?? null,
            ]);

            $results[] = [
                'uuid' => $visitData['uuid'],
                'status' => 'synced',
                'visit_id' => $pendingVisit->id,
            ];
        } else {
            $visit = CustomerVisit::create([
                'employee_id' => $employee->id,
                'customer_id' => $visitData['customer_id'],
                'route_id' => $visitData['route_id'] ?? null,
                'visit_date' => $visitData['visit_date'],
                'check_in_time' => !empty($visitData['check_in_time']) ? $visitData['check_in_time'] : now()->format('H:i:s'),
                'check_out_time' => !empty($visitData['check_out_time']) ? $visitData['check_out_time'] : now()->format('H:i:s'),
                'visit_status' => $visitData['visit_status'],
                'visit_reason' => $visitData['visit_reason'] ?? null,
                'latitude' => $visitData['latitude'] ?? null,
                'longitude' => $visitData['longitude'] ?? null,
                'notes' => $visitData['notes'] ?? null,
            ]);

            $results[] = [
                'uuid' => $visitData['uuid'],
                'status' => 'synced',
                'visit_id' => $visit->id,
            ];
        }
    }

    return response()->json(['message' => 'تمت مزامنة الزيارات', 'data' => $results]);
});

RouteFacade::post('handheld/sync-collections', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $request->validate([
        'collections' => 'required|array|min:1',
        'collections.*.uuid' => 'required|string',
        'collections.*.customer_id' => 'required|exists:customers,id',
        'collections.*.amount' => 'required|numeric|min:0.01',
        'collections.*.collection_date' => 'required|date',
        'collections.*.payment_method_id' => 'nullable|exists:payment_methods,id',
        'collections.*.reference_no' => 'nullable|string|max:100',
        'collections.*.notes' => 'nullable|string',
        'collections.*.branch_id' => 'nullable|exists:branches,id',
    ]);

    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'الموظف غير موجود في النظام'], 404);
    }

    $results = [];

    foreach ($request->collections as $collectionData) {
        $existing = \App\Models\Sales\Collection::where('company_id', $user->company_id)
            ->where('customer_id', $collectionData['customer_id'])
            ->where('amount', $collectionData['amount'])
            ->whereDate('collection_date', $collectionData['collection_date'])
            ->where('created_by', $employee->id)
            ->first();

        if ($existing) {
            $results[] = [
                'uuid' => $collectionData['uuid'],
                'status' => 'already_synced',
                'collection_id' => $existing->id,
                'collection_no' => $existing->collection_no,
            ];
            continue;
        }

        $collection = \App\Models\Sales\Collection::create([
            'company_id' => $user->company_id,
            'branch_id' => $collectionData['branch_id'] ?? $request->input('branch_id'),
            'collection_date' => $collectionData['collection_date'],
            'collection_time' => now()->format('H:i:s'),
            'sales_rep_id' => $employee->id,
            'customer_id' => $collectionData['customer_id'],
            'payment_method_id' => $collectionData['payment_method_id'] ?? null,
            'amount' => $collectionData['amount'],
            'reference_no' => $collectionData['reference_no'] ?? null,
            'notes' => $collectionData['notes'] ?? 'تحصيل من جهاز المندوب - تم المزامنة',
            'status' => 'approved',
            'created_by' => $employee->id,
        ]);

        $results[] = [
            'uuid' => $collectionData['uuid'],
            'status' => 'synced',
            'collection_id' => $collection->id,
            'collection_no' => $collection->collection_no,
        ];
    }

    return response()->json(['message' => 'تمت مزامنة التحصيلات', 'data' => $results]);
});

RouteFacade::get('handheld/salesmen-list', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    $salesmen = Employee::where('company_id', $user->company_id)
        ->where('is_active', true)
        ->select('id', 'first_name_ar', 'second_name_ar', 'third_name_ar', 'last_name_ar', 'first_name_en', 'second_name_en', 'third_name_en', 'last_name_en')
        ->orderBy('first_name_ar')
        ->get()
        ->map(fn($e) => [
            'id' => $e->id,
            'name' => $e->full_name_ar ?: $e->full_name_en ?: ($e->first_name_ar ?? ''),
        ]);

    return response()->json(['data' => $salesmen]);
});

RouteFacade::get('handheld/rep-summary', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    if (!$employee) {
        $employee = Employee::where('email', $user->email)->first();
    }

    if (!$employee) {
        $representative = \App\Models\Representative::where('user_id', $user->id)->first();
        if ($representative) {
            $employee = Employee::where('national_id', $representative->code)->first();
        }
    }

    if (!$employee) {
        return response()->json(['message' => 'الموظف غير موجود'], 404);
    }

    $targetEmployeeId = $request->input('employee_id') ? (int) $request->input('employee_id') : $employee->id;
    $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
    $dateTo = $request->input('date_to', now()->toDateString());

    $targetEmployee = Employee::find($targetEmployeeId);

    $visits = CustomerVisit::where('employee_id', $targetEmployeeId)
        ->where('visit_date', '>=', $dateFrom)
        ->where('visit_date', '<=', $dateTo . ' 23:59:59')
        ->with('customer:id,name_ar,code')
        ->orderByDesc('visit_date')
        ->orderByDesc('id')
        ->get();

    $visitStats = [
        'total' => $visits->count(),
        'completed' => $visits->where('visit_status', 'completed')->count(),
        'cancelled' => $visits->where('visit_status', 'cancelled')->count(),
        'pending' => $visits->where('visit_status', 'pending')->count(),
    ];

    $invoices = SalesInvoice::where('sales_rep_id', $targetEmployeeId)
        ->where('company_id', $user->company_id)
        ->where('invoice_date', '>=', $dateFrom)
        ->where('invoice_date', '<=', $dateTo . ' 23:59:59')
        ->whereNull('deleted_at')
        ->with('customer:id,name_ar,code')
        ->orderByDesc('invoice_date')
        ->orderByDesc('id')
        ->get();

    $invoiceStats = [
        'total_count' => $invoices->count(),
        'total_amount' => (float) $invoices->sum('net_total'),
        'total_paid' => (float) $invoices->sum('paid_amount'),
        'total_remaining' => (float) $invoices->sum('remaining_amount'),
    ];

    $collections = \App\Models\Sales\Collection::where('sales_rep_id', $targetEmployeeId)
        ->where('company_id', $user->company_id)
        ->where('collection_date', '>=', $dateFrom)
        ->where('collection_date', '<=', $dateTo . ' 23:59:59')
        ->with('customer:id,name_ar,code')
        ->orderByDesc('collection_date')
        ->orderByDesc('id')
        ->get();

    $collectionStats = [
        'total_count' => $collections->count(),
        'total_amount' => (float) $collections->sum('amount'),
    ];

    $returnOrders = ReturnOrder::where('employee_id', $targetEmployeeId)
        ->where('return_date', '>=', $dateFrom)
        ->where('return_date', '<=', $dateTo . ' 23:59:59')
        ->with('items.item:id,name_ar')
        ->orderByDesc('return_date')
        ->orderByDesc('id')
        ->get();

    $returnStats = [
        'total_count' => $returnOrders->count(),
        'total_amount' => (float) $returnOrders->sum('total_amount'),
    ];

    return response()->json([
        'data' => [
            'employee' => [
                'id' => $targetEmployee->id,
                'name' => $targetEmployee->full_name_ar ?: $targetEmployee->full_name_en ?: ($targetEmployee->first_name_ar ?? ''),
            ],
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'visits' => $visitStats,
            'invoices' => $invoiceStats,
            'collections' => $collectionStats,
            'return_orders' => $returnStats,
            'visits_list' => $visits->map(fn($v) => [
                'id' => $v->id,
                'customer_name' => $v->customer?->name_ar ?? '',
                'customer_code' => $v->customer?->code ?? '',
                'visit_date' => $v->visit_date?->toDateString(),
                'check_in_time' => $v->check_in_time,
                'check_out_time' => $v->check_out_time,
                'visit_status' => $v->visit_status,
                'notes' => $v->notes,
            ]),
            'invoices_list' => $invoices->map(fn($inv) => [
                'id' => $inv->id,
                'invoice_no' => $inv->invoice_no,
                'customer_name' => $inv->customer?->name_ar ?? '',
                'invoice_date' => $inv->invoice_date?->toDateString(),
                'net_total' => (float) $inv->net_total,
                'paid_amount' => (float) $inv->paid_amount,
                'remaining_amount' => (float) $inv->remaining_amount,
                'status' => $inv->status,
            ]),
            'collections_list' => $collections->map(fn($c) => [
                'id' => $c->id,
                'collection_no' => $c->collection_no,
                'customer_name' => $c->customer?->name_ar ?? '',
                'collection_date' => $c->collection_date?->toDateString(),
                'amount' => (float) $c->amount,
                'status' => $c->status,
            ]),
            'return_orders_list' => $returnOrders->map(fn($r) => [
                'id' => $r->id,
                'return_no' => $r->return_no,
                'return_date' => $r->return_date?->toDateString(),
                'total_amount' => (float) $r->total_amount,
                'status' => $r->status_id,
                'items_count' => $r->items->count(),
            ]),
        ],
    ]);
});

RouteFacade::get('handheld/daily-summary', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    $today = now()->toDateString();

    $visitsCount = 0;
    $salesCount = 0;
    $totalSales = 0;
    $totalPaid = 0;
    $totalRemaining = 0;
    $collectionsFromBalance = 0;
    $todaySettlement = null;
    $expenses = [];
    $totalExpenses = 0;
    $expectedCash = 0;
    $pendingDebts = 0;

    if ($employee) {
        $visitsCount = CustomerVisit::where('employee_id', $employee->id)
            ->whereDate('visit_date', $today)
            ->where('company_id', $user->company_id)
            ->count();

        $invoices = SalesInvoice::where('sales_rep_id', $employee->id)
            ->whereDate('invoice_date', $today)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->get();

        $salesCount = $invoices->count();
        $totalSales = (float) $invoices->sum('net_total');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $totalRemaining = $totalSales - $totalPaid;

        $collectionsFromBalance = \App\Models\Sales\Collection::where('sales_rep_id', $employee->id)
            ->whereDate('collection_date', $today)
            ->where('company_id', $user->company_id)
            ->whereNull('deleted_at')
            ->where('notes', 'like', '%الدفع من رصيد سابق%')
            ->sum('amount');

        $todaySettlement = \App\Models\Sales\RepDailySettlement::where('sales_rep_id', $employee->id)
            ->whereDate('settlement_date', $today)
            ->where('company_id', $user->company_id)
            ->first();

        if ($todaySettlement) {
            $expenses = $todaySettlement->expenses->map(fn($e) => [
                'id' => $e->id,
                'expense_type' => $e->expense_type,
                'amount' => (float) $e->amount,
                'notes' => $e->notes,
            ])->toArray();
        }

        $assignment = DB::table('vehicle_assignments')
            ->where('sales_rep_id', $employee->id)
            ->where('status', 'active')
            ->first();
        if ($assignment) {
            $vehicleExpenses = \App\Models\VehicleDailyExpense::where('vehicle_id', $assignment->vehicle_id)
                ->whereDate('expense_date', $today)
                ->whereNull('deleted_at')
                ->get()
                ->map(fn($ve) => [
                    'id' => $ve->id,
                    'expense_type' => $ve->expense_type,
                    'amount' => (float) $ve->amount,
                    'notes' => $ve->notes,
                    'source' => 'vehicle',
                ])->toArray();
            $expenses = array_merge($expenses, $vehicleExpenses);
        }

        $totalExpenses = collect($expenses)->sum('amount');

        $pendingDebts = \App\Models\Sales\SalesmanDebt::where('salesman_id', $employee->id)
            ->where('company_id', $user->company_id)
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum('remaining_debt');

        $expectedCash = $totalPaid - $totalExpenses - (float) $collectionsFromBalance;
    }

    return response()->json([
        'data' => [
            'visits_count' => $visitsCount,
            'sales_count' => $salesCount,
            'total_sales' => round($totalSales, 2),
            'total_paid' => round($totalPaid, 2),
            'total_remaining' => round($totalRemaining, 2),
            'collections_from_balance' => round((float) $collectionsFromBalance, 2),
            'expenses' => $expenses,
            'total_expenses' => round($totalExpenses, 2),
            'expected_cash' => round($expectedCash, 2),
            'pending_debts' => round((float) $pendingDebts, 2),
            'settlement_id' => $todaySettlement?->id,
        ],
    ]);
});

RouteFacade::post('handheld/submit-settlement', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'المندوب غير موجود'], 404);
    }

    $uuid = $request->input('uuid');
    if ($uuid) {
        $existing = \App\Models\Sales\RepDailySettlement::where('company_id', $user->company_id)
            ->where('sales_rep_id', $employee->id)
            ->where('notes', 'like', "%uuid:$uuid%")
            ->first();
        if ($existing) {
            return response()->json(['message' => 'تم بالفعل', 'data' => ['settlement_id' => $existing->id]], 200);
        }
    }

    $request->validate([
        'actual_cash' => 'required|numeric|min:0',
        'expenses' => 'nullable|array',
        'expenses.*.expense_type' => 'required|string|max:100',
        'expenses.*.amount' => 'required|numeric|min:0.01',
        'expenses.*.notes' => 'nullable|string',
        'notes' => 'nullable|string',
        'branch_id' => 'nullable|integer',
    ]);

    $today = now()->toDateString();

    $branchId = null;
    $assignment = DB::table('salesman_assignments')
        ->where('employee_id', $employee->id)
        ->where('is_active', true)
        ->where('job_role', 'salesman')
        ->first();
    if ($assignment && $assignment->sales_territory_id) {
        $territory = DB::table('sales_territories')
            ->where('id', $assignment->sales_territory_id)
            ->first();
        if ($territory) {
            $branchId = $territory->branch_id;
        }
    }
    if (!$branchId) {
        $branchId = $request->input('branch_id');
    }

    $existingSettlement = \App\Models\Sales\RepDailySettlement::where('sales_rep_id', $employee->id)
        ->whereDate('settlement_date', $today)
        ->where('company_id', $user->company_id)
        ->first();

    if ($existingSettlement && $existingSettlement->status === 'approved') {
        return response()->json(['message' => 'التسوية معتمدة بالفعل'], 422);
    }

    $invoices = SalesInvoice::where('sales_rep_id', $employee->id)
        ->whereDate('invoice_date', $today)
        ->where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->get();

    $totalSales = (float) $invoices->sum('net_total');
    $totalPaid = (float) $invoices->sum('paid_amount');

    $totalExpenses = collect($request->expenses ?? [])->sum('amount');

    $collectionsFromBalance = \App\Models\Sales\Collection::where('sales_rep_id', $employee->id)
        ->whereDate('collection_date', $today)
        ->where('company_id', $user->company_id)
        ->whereNull('deleted_at')
        ->where('notes', 'like', '%الدفع من رصيد سابق%')
        ->sum('amount');

    $expectedCash = $totalPaid - $totalExpenses - (float) $collectionsFromBalance;
    $actualCash = (float) $request->actual_cash;
    $cashDifference = $actualCash - $expectedCash;
    $shortage = $cashDifference < 0 ? abs($cashDifference) : 0;

    $shortageStatus = $shortage > 0 ? 'pending' : 'paid_next_day';
    $salesmanDebtId = null;

    if ($existingSettlement) {
        $notes = $request->notes;
        if ($uuid) $notes = ($notes ? "$notes " : '') . "uuid:$uuid";
        $existingSettlement->update([
            'total_sales_value' => round($totalSales, 2),
            'total_collections_value' => round($totalPaid, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_from_balance' => round((float) $collectionsFromBalance, 2),
            'expected_cash' => round($expectedCash, 2),
            'actual_cash' => round($actualCash, 2),
            'cash_difference' => round($cashDifference, 2),
            'shortage' => round($shortage, 2),
            'shortage_status' => $shortageStatus,
            'notes' => $notes,
            'status' => 'submitted',
            'branch_id' => $branchId,
            'created_by' => $employee->id,
        ]);

        $existingSettlement->expenses()->delete();
        $settlement = $existingSettlement;
    } else {
        $notes = $request->notes;
        if ($uuid) $notes = ($notes ? "$notes " : '') . "uuid:$uuid";
        $settlement = \App\Models\Sales\RepDailySettlement::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'settlement_date' => $today,
            'sales_rep_id' => $employee->id,
            'total_sales_value' => round($totalSales, 2),
            'total_collections_value' => round($totalPaid, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_from_balance' => round((float) $collectionsFromBalance, 2),
            'expected_cash' => round($expectedCash, 2),
            'actual_cash' => round($actualCash, 2),
            'cash_difference' => round($cashDifference, 2),
            'shortage' => round($shortage, 2),
            'shortage_status' => $shortageStatus,
            'notes' => $notes,
            'status' => 'submitted',
            'created_by' => $employee->id,
        ]);
    }

    foreach ($request->expenses ?? [] as $expense) {
        \App\Models\Sales\RepDailyExpense::create([
            'company_id' => $user->company_id,
            'settlement_id' => $settlement->id,
            'expense_type' => $expense['expense_type'],
            'amount' => $expense['amount'],
            'notes' => $expense['notes'] ?? null,
        ]);
    }

    if ($shortage > 0) {
        $salesmanAccount = \App\Models\Sales\SalesmanAccount::where('salesman_id', $employee->id)
            ->where('company_id', $user->company_id)
            ->first();

        $debt = \App\Models\Sales\SalesmanDebt::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'salesman_id' => $employee->id,
            'salesman_account_id' => $salesmanAccount?->id,
            'debt_date' => $today,
            'total_sales' => round($totalSales, 2),
            'total_returns' => 0,
            'gross_debt' => round($shortage, 2),
            'total_paid' => 0,
            'remaining_debt' => round($shortage, 2),
            'status' => 'pending',
            'notes' => "عجز تسوية يومية {$settlement->settlement_no}",
            'created_by' => $employee->id,
        ]);

        $salesmanDebtId = $debt->id;
        $settlement->update(['salesman_debt_id' => $debt->id]);

        if ($salesmanAccount) {
            $salesmanAccount->update([
                'total_debts' => $salesmanAccount->total_debts + $shortage,
                'current_balance' => $salesmanAccount->current_balance + $shortage,
            ]);

            \App\Models\Sales\SalesmanAccountMovement::create([
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'salesman_account_id' => $salesmanAccount->id,
                'salesman_id' => $employee->id,
                'movement_date' => $today,
                'movement_type' => 'debt_creation',
                'reference_type' => \App\Models\Sales\SalesmanDebt::class,
                'reference_id' => $debt->id,
                'document_no' => $debt->debt_no,
                'debit' => round($shortage, 2),
                'credit' => 0,
                'balance' => $salesmanAccount->current_balance + $shortage,
                'description' => "عجز تسوية يومية {$settlement->settlement_no}",
                'created_by' => $employee->id,
            ]);
        }
    }

    return response()->json([
        'message' => 'تم تسجيل التسوية بنجاح',
        'data' => [
            'settlement_id' => $settlement->id,
            'settlement_no' => $settlement->settlement_no,
            'expected_cash' => (float) $settlement->expected_cash,
            'actual_cash' => (float) $settlement->actual_cash,
            'cash_difference' => (float) $settlement->cash_difference,
            'shortage' => (float) $settlement->shortage,
            'total_expenses' => (float) $settlement->total_expenses,
            'salesman_debt_id' => $salesmanDebtId,
        ],
    ], 201);
});

RouteFacade::get('handheld/my-settlements', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['data' => []]);
    }

    $settlements = \App\Models\Sales\RepDailySettlement::where('sales_rep_id', $employee->id)
        ->where('company_id', $user->company_id)
        ->orderByDesc('settlement_date')
        ->limit(30)
        ->get()
        ->map(fn($s) => [
            'id' => $s->id,
            'settlement_no' => $s->settlement_no,
            'settlement_date' => $s->settlement_date?->toDateString(),
            'total_sales_value' => (float) $s->total_sales_value,
            'total_collections_value' => (float) $s->total_collections_value,
            'total_expenses' => (float) $s->total_expenses,
            'expected_cash' => (float) $s->expected_cash,
            'actual_cash' => (float) $s->actual_cash,
            'cash_difference' => (float) $s->cash_difference,
            'shortage' => (float) $s->shortage,
            'shortage_status' => $s->shortage_status,
            'status' => $s->status,
        ]);

    return response()->json(['data' => $settlements]);
});

RouteFacade::post('handheld/add-expense', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    if (!$employee) {
        return response()->json(['message' => 'المندوب غير موجود'], 404);
    }

    $request->validate([
        'expense_type' => 'required|string|max:100',
        'amount' => 'required|numeric|min:0.01',
        'notes' => 'nullable|string',
    ]);

    $today = now()->toDateString();

    $settlement = \App\Models\Sales\RepDailySettlement::where('sales_rep_id', $employee->id)
        ->whereDate('settlement_date', $today)
        ->where('company_id', $user->company_id)
        ->first();

    if (!$settlement) {
        $branchId = null;
        $assignment = DB::table('salesman_assignments')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->where('job_role', 'salesman')
            ->first();
        if ($assignment && $assignment->sales_territory_id) {
            $territory = DB::table('sales_territories')
                ->where('id', $assignment->sales_territory_id)
                ->first();
            if ($territory) {
                $branchId = $territory->branch_id;
            }
        }

        $settlement = \App\Models\Sales\RepDailySettlement::create([
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'settlement_date' => $today,
            'sales_rep_id' => $employee->id,
            'status' => 'draft',
            'created_by' => $employee->id,
        ]);
    }

    $expense = \App\Models\Sales\RepDailyExpense::create([
        'company_id' => $user->company_id,
        'settlement_id' => $settlement->id,
        'expense_type' => $request->expense_type,
        'amount' => $request->amount,
        'notes' => $request->notes,
    ]);

    $totalExpenses = $settlement->expenses()->sum('amount');
    $settlement->update(['total_expenses' => round($totalExpenses, 2)]);

    return response()->json([
        'message' => 'تم إضافة المصروف بنجاح',
        'data' => [
            'expense_id' => $expense->id,
            'expense_type' => $expense->expense_type,
            'amount' => (float) $expense->amount,
            'total_expenses' => round($totalExpenses, 2),
        ],
    ], 201);
});

RouteFacade::post('handheld/car-expenses', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    $validated = $request->validate([
        'expense_type' => 'required|string|max:50',
        'amount' => 'required|numeric|min:0',
        'notes' => 'nullable|string',
        'expense_date' => 'required|date',
    ]);

    $vehicleId = null;
    if ($employee) {
        $assignment = DB::table('vehicle_assignments')
            ->where('sales_rep_id', $employee->id)
            ->where('status', 'active')
            ->first();
        if ($assignment) {
            $vehicleId = $assignment->vehicle_id;
        }
    }

    $expense = \App\Models\VehicleDailyExpense::create([
        'vehicle_id' => $vehicleId,
        'expense_date' => $validated['expense_date'],
        'expense_type' => strtoupper($validated['expense_type']),
        'amount' => $validated['amount'],
        'notes' => $validated['notes'] ?? null,
        'created_by' => $user->id,
    ]);

    return response()->json([
        'message' => 'تم إضافة المصروف بنجاح',
        'data' => $expense,
    ], 201);
});

RouteFacade::get('handheld/car-expenses', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    $query = \App\Models\VehicleDailyExpense::query();

    if ($employee) {
        $assignment = DB::table('vehicle_assignments')
            ->where('sales_rep_id', $employee->id)
            ->where('status', 'active')
            ->first();
        if ($assignment) {
            $query->where('vehicle_id', $assignment->vehicle_id);
        }
    }

    if ($request->filled('expense_date')) {
        $query->where('expense_date', $request->expense_date);
    } else {
        $query->where('expense_date', now()->toDateString());
    }

    if ($request->filled('expense_type')) {
        $query->where('expense_type', $request->expense_type);
    }

    $expenses = $query->orderByDesc('id')->get();

    return response()->json(['data' => $expenses]);
});

RouteFacade::post('handheld/sync-car-expenses', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $employee = resolveEmployee($request);

    $request->validate([
        'expenses' => 'required|array|min:1',
        'branch_id' => 'nullable|integer',
    ]);

    $expenses = $request->input('expenses', []);
    $requestBranchId = $request->input('branch_id');
    $results = ['synced' => 0, 'failed' => 0, 'errors' => []];

    foreach ($expenses as $exp) {
        try {
            $uuid = $exp['uuid'] ?? null;
            if ($uuid) {
                $existing = \App\Models\VehicleDailyExpense::where('company_id', $user->company_id)
                    ->where('notes', 'like', "%uuid:$uuid%")
                    ->first();
                if ($existing) {
                    $results['synced']++;
                    continue;
                }
            }

            $vehicleId = null;
            if ($employee) {
                $assignment = DB::table('vehicle_assignments')
                    ->where('sales_rep_id', $employee->id)
                    ->where('status', 'active')
                    ->first();
                if ($assignment) {
                    $vehicleId = $assignment->vehicle_id;
                }
            }

            $notes = $exp['notes'] ?? null;
            if ($uuid) $notes = ($notes ? "$notes " : '') . "uuid:$uuid";
            \App\Models\VehicleDailyExpense::create([
                'vehicle_id' => $vehicleId,
                'expense_date' => $exp['expense_date'] ?? now()->toDateString(),
                'expense_type' => strtoupper($exp['expense_type'] ?? 'OTHER'),
                'amount' => $exp['amount'] ?? 0,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);
            $results['synced']++;
        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][] = $e->getMessage();
        }
    }

    return response()->json(['message' => 'تمت المزامنة', 'data' => $results]);
});

