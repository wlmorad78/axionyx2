<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('health-check', function () {
    return response()->json(['status' => 'Server is running', 'timestamp' => now()]);
});

// Postman collection
Route::get('postman-collection', function () {
    $path = storage_path('api-docs/postman_collection.json');
    if (! file_exists($path)) abort(404, 'Postman collection not found');
    return response()->download($path, 'Axionyx_ERP_API.postman_collection.json', ['Content-Type' => 'application/json']);
});

// Public: Login (no auth required)
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Protected: require auth for all non-login routes below
Route::middleware('auth:sanctum')->group(function () {

// Dynamic menu
Route::get('menu/sidebar', [\App\Http\Controllers\Api\Permissions\MenuController::class, 'sidebar']);

// Company sidebar management
Route::get('company-sidebar', [\App\Http\Controllers\Api\CompanySidebarController::class, 'index']);
Route::post('company-sidebar', [\App\Http\Controllers\Api\CompanySidebarController::class, 'store']);
Route::delete('company-sidebar', [\App\Http\Controllers\Api\CompanySidebarController::class, 'destroy']);

// Dashboard widgets
Route::get('dashboard', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'dashboard']);
Route::get('dashboard/widgets', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'index']);
Route::post('dashboard/widgets', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'store']);
Route::get('dashboard/widgets/{widget}', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'show']);
Route::put('dashboard/widgets/{widget}', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'update']);
Route::delete('dashboard/widgets/{widget}', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'destroy']);
Route::get('dashboard/widgets/role/{role}', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'roleWidgets']);
Route::post('dashboard/widgets/role/{role}', [\App\Http\Controllers\Api\Reports\DashboardWidgetController::class, 'syncRoleWidgets']);

// Audit logs
Route::get('audit-logs/stats', [\App\Http\Controllers\Api\AuditLogController::class, 'stats']);
Route::apiResource('audit-logs', \App\Http\Controllers\Api\AuditLogController::class)->only(['index', 'show']);

// Notifications
Route::get('notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
Route::get('notifications/stats', [\App\Http\Controllers\Api\NotificationController::class, 'stats']);
Route::put('notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
Route::put('notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
Route::apiResource('notifications', \App\Http\Controllers\Api\NotificationController::class)->only(['index', 'destroy']);

// Approvals
Route::get('approvals/stats', [\App\Http\Controllers\Api\ApprovalController::class, 'stats']);
Route::post('approvals/{approval}/approve', [\App\Http\Controllers\Api\ApprovalController::class, 'approve']);
Route::post('approvals/{approval}/reject', [\App\Http\Controllers\Api\ApprovalController::class, 'reject']);
Route::apiResource('approvals', \App\Http\Controllers\Api\ApprovalController::class)->only(['index', 'show']);

// Company settings (flexible key-value)
Route::get('company-settings', [\App\Http\Controllers\Api\CompanySettingController::class, 'index']);
Route::get('company-settings/{group}', [\App\Http\Controllers\Api\CompanySettingController::class, 'byGroup']);
Route::put('company-settings', [\App\Http\Controllers\Api\CompanySettingController::class, 'update']);
Route::delete('company-settings/{group}/{key}', [\App\Http\Controllers\Api\CompanySettingController::class, 'destroy']);

// Module Manager
Route::get('modules/manifest', [\App\Http\Controllers\Api\ModuleController::class, 'manifest']);
Route::get('modules/{code}/permissions', [\App\Http\Controllers\Api\ModuleController::class, 'permissions']);
Route::get('modules/{code}/menu', [\App\Http\Controllers\Api\ModuleController::class, 'menu']);
Route::post('modules/{code}/install', [\App\Http\Controllers\Api\ModuleController::class, 'install']);
Route::delete('modules/{code}/uninstall', [\App\Http\Controllers\Api\ModuleController::class, 'uninstall']);
Route::put('modules/{code}/enable', [\App\Http\Controllers\Api\ModuleController::class, 'enable']);
Route::put('modules/{code}/disable', [\App\Http\Controllers\Api\ModuleController::class, 'disable']);
Route::post('modules/{code}/upgrade', [\App\Http\Controllers\Api\ModuleController::class, 'upgrade']);
Route::apiResource('modules', \App\Http\Controllers\Api\ModuleController::class)->only(['index', 'show']);

// Event Bus
Route::get('events/stats', [\App\Http\Controllers\Api\EventController::class, 'stats']);
Route::get('events/history', [\App\Http\Controllers\Api\EventController::class, 'history']);
Route::get('events/{code}/subscriptions', [\App\Http\Controllers\Api\EventController::class, 'subscriptions']);
Route::post('events/{code}/subscribe', [\App\Http\Controllers\Api\EventController::class, 'subscribe']);
Route::delete('events/{code}/unsubscribe', [\App\Http\Controllers\Api\EventController::class, 'unsubscribe']);
Route::post('events/{code}/fire', [\App\Http\Controllers\Api\EventController::class, 'fire']);
Route::apiResource('events', \App\Http\Controllers\Api\EventController::class)->only(['index', 'show']);

// Resource next-code routes
Route::get('customers/next-code', [\App\Http\Controllers\Api\CustomerController::class, 'nextCode']);
Route::get('customer-groups/next-code', [\App\Http\Controllers\Api\CustomerGroupController::class, 'nextCode']);
Route::get('customer-classes/next-code', [\App\Http\Controllers\Api\CustomerClassController::class, 'nextCode']);
Route::get('customer-types/next-code', [\App\Http\Controllers\Api\CustomerTypeController::class, 'nextCode']);
Route::get('customer-account-types/next-code', [\App\Http\Controllers\Api\CustomerAccountTypeController::class, 'nextCode']);
Route::get('trade-program-types/next-code', [\App\Http\Controllers\Api\TradeProgramTypeController::class, 'nextCode']);
Route::get('warehouses/next-code', [\App\Http\Controllers\Api\WarehouseController::class, 'nextCode']);
Route::get('companies/next-code', [\App\Http\Controllers\Api\CompanyController::class, 'nextCode']);
Route::get('branches/next-code', [\App\Http\Controllers\Api\BranchController::class, 'nextCode']);
Route::get('employees/next-code', [\App\Http\Controllers\Api\EmployeeController::class, 'nextCode']);
Route::get('users/next-code', [\App\Http\Controllers\Api\UserController::class, 'nextCode']);
Route::get('employee-contracts/next-code', [\App\Http\Controllers\Api\EmployeeContractController::class, 'nextCode']);
Route::get('employee-contract-amendments/next-code', [\App\Http\Controllers\Api\EmployeeContractAmendmentController::class, 'nextCode']);
Route::get('employee-loans/next-code', [\App\Http\Controllers\Api\EmployeeLoanController::class, 'nextCode']);
Route::get('employee-advances/next-code', [\App\Http\Controllers\Api\EmployeeAdvanceController::class, 'nextCode']);
Route::get('departments/next-code', [\App\Http\Controllers\Api\DepartmentController::class, 'nextCode']);
Route::get('position-levels/next-code', [\App\Http\Controllers\Api\PositionLevelController::class, 'nextCode']);
Route::get('job-positions/next-code', [\App\Http\Controllers\Api\JobPositionController::class, 'nextCode']);
Route::get('job-families/next-code', [\App\Http\Controllers\Api\JobFamilyController::class, 'nextCode']);
Route::get('job-titles/next-code', [\App\Http\Controllers\Api\JobTitleController::class, 'nextCode']);
Route::get('job-grades/next-code', [\App\Http\Controllers\Api\JobGradeController::class, 'nextCode']);
Route::get('salary-scales/next-code', [\App\Http\Controllers\Api\SalaryScaleController::class, 'nextCode']);
Route::get('sales-territories/next-code', [\App\Http\Controllers\Api\SalesTerritoryController::class, 'nextCode']);
Route::get('sales-territory-types/next-code', function () { return response()->json(['code' => 'STT-00001']); });
Route::get('organization-units/next-code', [\App\Http\Controllers\Api\OrganizationUnitController::class, 'nextCode']);
Route::get('cost-centers/next-code', [\App\Http\Controllers\Api\CostCenterController::class, 'nextCode']);
Route::get('treasuries/next-code', [\App\Http\Controllers\Api\TreasuryController::class, 'nextCode']);
Route::get('stock-adjustments/next-code', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'nextCode']);
Route::get('stock-counts/next-code', [\App\Http\Controllers\Api\StockCountController::class, 'nextCode']);
Route::get('warehouse-transfers/next-code', [\App\Http\Controllers\Api\WarehouseTransferController::class, 'nextCode']);
Route::get('inventory-transactions/next-code', [\App\Http\Controllers\Api\InventoryTransactionController::class, 'nextCode']);
Route::get('inventory-revaluations/next-code', [\App\Http\Controllers\Api\InventoryRevaluationController::class, 'nextCode']);
Route::get('journal-entries/next-code', [\App\Http\Controllers\Api\JournalEntryController::class, 'nextCode']);
Route::get('manual-journal-entries/next-code', [\App\Http\Controllers\Api\ManualJournalEntryController::class, 'nextCode']);
Route::get('receipt-vouchers/next-code', [\App\Http\Controllers\Api\ReceiptVoucherController::class, 'nextCode']);
Route::get('payment-vouchers/next-code', [\App\Http\Controllers\Api\PaymentVoucherController::class, 'nextCode']);
Route::get('suppliers/{id}/statement', [\App\Http\Controllers\Api\SupplierController::class, 'statement']);
Route::get('suppliers/{id}/unpaid-invoices', function ($id) {
    $invoices = \App\Models\PurchaseInvoice::where('supplier_id', $id)
        ->where('status', '!=', 'cancelled')
        ->whereRaw('net_total - paid_amount > 0')
        ->orderByDesc('invoice_date')
        ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'remaining_amount']);
    return response()->json($invoices);
});
Route::get('bank-transfers/next-code', [\App\Http\Controllers\Api\BankTransferController::class, 'nextCode']);
Route::get('items/next-code', [\App\Http\Controllers\Api\ItemController::class, 'nextCode']);
Route::get('item-categories/next-code', [\App\Http\Controllers\Api\ItemCategoryController::class, 'nextCode']);
Route::get('item-sub-categories/next-code', [\App\Http\Controllers\Api\ItemSubCategoryController::class, 'nextCode']);
Route::get('product-companies/next-code', [\App\Http\Controllers\Api\ProductCompanyController::class, 'nextCode']);
Route::get('accounts/next-code', [\App\Http\Controllers\Api\AccountController::class, 'nextCode']);
Route::get('sales-routes/next-code', [\App\Http\Controllers\Api\SalesRouteController::class, 'nextCode']);
Route::get('dashboard', [\App\Http\Controllers\Api\Reports\DashboardController::class, 'index'])->middleware('auth:sanctum')->name('dashboard.index');
Route::get('reports/sales', [\App\Http\Controllers\Api\ReportController::class, 'sales'])->middleware('auth:sanctum')->name('reports.sales');
Route::get('reports/purchases', [\App\Http\Controllers\Api\ReportController::class, 'purchases'])->middleware('auth:sanctum')->name('reports.purchases');
Route::get('reports/inventory', [\App\Http\Controllers\Api\ReportController::class, 'inventory'])->middleware('auth:sanctum')->name('reports.inventory');
Route::get('reports/profit', [\App\Http\Controllers\Api\ReportController::class, 'profit'])->name('reports.profit');

// Item Movement Report
Route::get('reports/item-movement', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'item_id' => 'required|integer',
    ]);

    $companyId = $request->input('company_id');
    $itemId = $request->input('item_id');
    $warehouseId = $request->input('warehouse_id');
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    $item = \App\Models\Item::with('baseUnit')->find($itemId);
    if (!$item) {
        return response()->json(['message' => 'الصنف غير موجود'], 404);
    }

    // Determine base unit name — use default unit (is_default=1) first, then fallback to conv=1
    $baseUnitName = $item->baseUnit?->name_ar ?? '';
    if (empty($baseUnitName)) {
        $defaultUnit = \App\Models\ItemUnit::where('item_id', $itemId)
            ->where('is_default', true)->first();
        if (!$defaultUnit) {
            $defaultUnit = \App\Models\ItemUnit::where('item_id', $itemId)
                ->where('conversion_factor', 1)->first();
        }
        $baseUnitName = $defaultUnit && $defaultUnit->unit
            ? $defaultUnit->unit->name_ar
            : 'وحدة';
    }

    // Opening balance from InventoryOpeningBalance
    $obQuery = \App\Models\InventoryOpeningBalance::query()
        ->where('item_id', $itemId)
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));

    $openingBalances = $obQuery->get();
    $openingQty = 0;
    foreach ($openingBalances as $ob) {
        $conversionFactor = 1;
        if (!empty($ob->unit_id)) {
            $iu = \App\Models\ItemUnit::where('item_id', $itemId)->where('unit_id', $ob->unit_id)->first();
            if ($iu && $iu->conversion_factor > 0) $conversionFactor = $iu->conversion_factor;
        }
        $openingQty += (float)$ob->qty * $conversionFactor;
    }

    // Posted transactions for this item
    $txnQuery = \App\Models\InventoryTransaction::query()
        ->whereHas('items', fn($q) => $q->where('item_id', $itemId))
        ->where('status', 'posted')
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
        ->when($dateFrom, fn($q) => $q->whereDate('transaction_date', '>=', $dateFrom))
        ->when($dateTo, fn($q) => $q->whereDate('transaction_date', '<=', $dateTo))
        ->with(['transactionType', 'warehouse'])
        ->orderBy('transaction_date', 'asc')
        ->orderBy('id', 'asc');

    $transactions = $txnQuery->get();

    $movements = [];
    $runningBalance = $openingQty;

    foreach ($transactions as $txn) {
        foreach ($txn->items->where('item_id', $itemId) as $txnItem) {
            $qty = (float)$txnItem->qty;
            $isAddition = $txn->transactionType?->effect === 'addition';
            $isSubtraction = $txn->transactionType?->effect === 'subtraction';

            $refShort = $txn->reference_type ? class_basename($txn->reference_type) : '';
            $isIssueOrder = ($refShort === 'IssueOrder');

            $qtyIn  = $isAddition ? abs($qty) : 0;
            $qtyOut = ($isSubtraction && !$isIssueOrder) ? abs($qty) : 0;
            $qtyLoad = ($isSubtraction && $isIssueOrder) ? abs($qty) : 0;

            $qtyReturn = 0;
            $txnTypeName = $txn->transactionType?->name ?? '';
            $referenceShort = $txn->reference_type ? class_basename($txn->reference_type) : '';
            $isReturnType = false;
            if ($referenceShort !== '' && stripos($referenceShort, 'return') !== false) {
                $isReturnType = true;
            }
            if (!$isReturnType && $txnTypeName !== '' && (stripos($txnTypeName, 'return') !== false || str_contains($txnTypeName, 'مرتجع'))) {
                $isReturnType = true;
            }
            if ($isReturnType) {
                $qtyReturn = abs($qty);
            }

            $runningBalance += $qty;

            $referenceType = null;
            $referenceNo = null;
            $relatedParty = null;

            if ($txn->reference_type) {
                $refShort = class_basename($txn->reference_type);
                $referenceType = match($refShort) {
                    'PurchaseInvoice' => 'فاتورة شراء',
                    'SalesInvoice' => 'فاتورة بيع',
                    'PurchaseReturn' => 'مرتجع شراء',
                    'SalesReturn' => 'مرتجع بيع',
                    'StockAdjustment' => 'تعديل مخزون',
                    'WarehouseTransfer' => 'نقل مخزون',
                    'InventoryOpeningBalance' => 'رصيد افتتاحي',
                    'IssueOrder' => 'إذن صرف',
                    'LoadRequest' => 'طلب تحميل',
                    'CustomerReturn' => 'مرتجع عميل',
                    'ReturnOrder' => 'طلب إرجاع',
                    default => $refShort,
                };

                try {
                    if ($txn->reference_type && $txn->reference_id) {
                        $refClass = $txn->reference_type;
                        if (class_exists($refClass)) {
                            $refModel = $refClass::find($txn->reference_id);
                        } else {
                            $refModel = null;
                        }
                    } else {
                        $refModel = null;
                    }
                    if ($refModel) {
                        $referenceNo = $refModel->invoice_no ?? $refModel->issue_no ?? $refModel->order_no ?? $refModel->receipt_no ?? $refModel->request_no ?? $txn->notes ?? '';

                        if (!empty($refModel->supplier_id) && method_exists($refModel, 'supplier')) {
                            $s = $refModel->supplier;
                            if ($s) $relatedParty = $s->supplier_name ?? $s->name_ar ?? $s->name ?? null;
                        }
                        if (!$relatedParty && !empty($refModel->employee_id) && method_exists($refModel, 'employee')) {
                            $emp = $refModel->employee;
                            if ($emp) {
                                $relatedParty = collect([$emp->first_name_ar, $emp->second_name_ar, $emp->third_name_ar, $emp->last_name_ar])->filter()->implode(' ');
                                if (empty($relatedParty)) $relatedParty = $emp->employee_code;
                            }
                        }
                        if (!$relatedParty && !empty($refModel->customer_id) && method_exists($refModel, 'customer')) {
                            $c = $refModel->customer;
                            if ($c) $relatedParty = $c->name_ar ?? $c->name ?? null;
                        }
                        if (!$relatedParty && !empty($refModel->to_warehouse_id) && method_exists($refModel, 'toWarehouse')) {
                            $w = $refModel->toWarehouse;
                            if ($w) $relatedParty = $w->name_ar ?? $w->name ?? null;
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error('Item movement report reference lookup failed', [
                        'txn_id' => $txn->id,
                        'ref_type' => $txn->reference_type,
                        'ref_id' => $txn->reference_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                if (empty($referenceNo)) $referenceNo = $txn->notes ?? '';
            }

            $movements[] = [
                'id' => $txnItem->id,
                'date' => $txn->transaction_date?->format('Y-m-d'),
                'transaction_no' => $txn->transaction_no,
                'transaction_type' => $txn->transactionType?->name ?? '',
                'effect' => $txn->transactionType?->effect ?? '',
                'reference_type' => $referenceType,
                'reference_no' => $referenceNo,
                'warehouse' => $txn->warehouse?->name_ar ?? $txn->warehouse?->name ?? '',
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'qty_load' => $qtyLoad,
                'qty_return' => $qtyReturn,
                'unit_name' => $baseUnitName,
                'balance' => $runningBalance,
                'unit_cost' => (float)$txnItem->unit_cost,
                'total_cost' => (float)$txnItem->total_cost,
                'notes' => $txn->notes ?? '',
                'related_party' => $relatedParty ?? '-',
                'reference_id' => $txn->reference_id,
            ];
        }
    }

    return response()->json([
        'item' => [
            'id' => $item->id,
            'code' => $item->code,
            'name_ar' => $item->name_ar,
            'name_en' => $item->name_en,
            'unit' => $baseUnitName,
        ],
        'opening_balance' => $openingQty,
        'closing_balance' => $runningBalance,
        'total_in' => collect($movements)->sum('qty_in'),
        'total_out' => collect($movements)->sum('qty_out'),
        'total_load' => collect($movements)->sum('qty_load'),
        'total_return' => collect($movements)->sum('qty_return'),
        'total_unit_in' => abs(collect($movements)->sum('unit_qty_in')),
        'total_unit_out' => abs(collect($movements)->sum('unit_qty_out')),
        'base_unit_name' => $baseUnitName,
        'movements' => $movements,
    ]);
});

// Item Ledger Report v2 (movement types, tabs, rep balances)
Route::get('reports/item-ledger', function (\Illuminate\Http\Request $request) {
    $request->validate(['item_id' => 'required|integer']);
    $companyId = $request->input('company_id') ?? $request->user()?->company_id;
    $itemId = (int) $request->input('item_id');
    $warehouseId = $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null;
    $repId = $request->input('rep_id') ? (int) $request->input('rep_id') : null;
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    $item = \App\Models\Item::find($itemId);
    if (!$item) return response()->json(['message' => 'الصنف غير موجود'], 404);

    $rows = \Illuminate\Support\Facades\DB::table('inventory_transaction_items')
        ->join('inventory_transactions', 'inventory_transaction_items.inventory_transaction_id', '=', 'inventory_transactions.id')
        ->join('inventory_transaction_types', 'inventory_transactions.transaction_type_id', '=', 'inventory_transaction_types.id')
        ->leftJoin('items', 'inventory_transaction_items.item_id', '=', 'items.id')
        ->where('inventory_transactions.status', 'posted')
        ->where('inventory_transactions.company_id', $companyId)
        ->where('inventory_transaction_items.item_id', $itemId)
        ->when($dateFrom, fn($q, $v) => $q->where('inventory_transactions.transaction_date', '>=', $v))
        ->when($dateTo, fn($q, $v) => $q->where('inventory_transactions.transaction_date', '<=', $v))
        ->when($repId, fn($q, $v) => $q->where(function($q) use ($v) {
            $q->where('from_location_type', 'rep')->where('from_location_id', $v)
              ->orWhere(function($q) use ($v) {
                  $q->where('to_location_type', 'rep')->where('to_location_id', $v);
              });
        }))
        ->select([
            'inventory_transaction_items.id', 'inventory_transaction_items.item_id',
            'inventory_transaction_items.qty', 'inventory_transaction_items.unit_cost',
            'inventory_transaction_items.total_cost',
            'inventory_transaction_items.from_location_type', 'inventory_transaction_items.from_location_id',
            'inventory_transaction_items.to_location_type', 'inventory_transaction_items.to_location_id',
            'inventory_transactions.id as transaction_id', 'inventory_transactions.transaction_date',
            'inventory_transactions.transaction_no', 'inventory_transactions.reference_type',
            'inventory_transactions.reference_id', 'inventory_transactions.warehouse_id',
            'inventory_transactions.notes as txn_notes',
            'inventory_transaction_types.code as txn_type_code',
            'inventory_transaction_types.name as txn_type_name',
            'items.name as item_name',
        ])
        ->orderBy('inventory_transactions.transaction_date')
        ->orderBy('inventory_transactions.id')
        ->get();

    $typeLabelMap = [
        'purchase' => 'شراء', 'purchase_return' => 'مرتجع شراء',
        'load' => 'تحميل', 'sale' => 'بيع',
        'return' => 'مرتجع', 'unload' => 'تفريغ',
        'transfer_rep' => 'تحويل مندوب', 'transfer_wh' => 'تحويل مخزني',
    ];

    $locationNameCache = [];
    $resolveLocation = function($type, $id) use (&$locationNameCache) {
        $key = "$type:$id";
        if (!isset($locationNameCache[$key])) {
            $locationNameCache[$key] = match($type) {
                'warehouse' => \App\Models\Warehouse::find($id)?->name ?? "مخزن #$id",
                'rep' => \App\Models\Employee::find($id)?->full_name_ar ?? "مندوب #$id",
                'customer' => \App\Models\Customer::find($id)?->name ?? "عميل #$id",
                'supplier' => \App\Models\Supplier::find($id)?->name ?? "مورد #$id",
                'vehicle' => "مركبة #$id",
                default => "#$type #$id",
            };
        }
        return $locationNameCache[$key];
    };

    $classifyMovement = function($row) {
        if ($row->from_location_type && $row->to_location_type) {
            return match(true) {
                $row->from_location_type === 'supplier' => 'purchase',
                $row->to_location_type === 'supplier' => 'purchase_return',
                $row->from_location_type === 'warehouse' && $row->to_location_type === 'rep' => 'load',
                $row->from_location_type === 'rep' && $row->to_location_type === 'warehouse' => 'return',
                $row->from_location_type === 'rep' && $row->to_location_type === 'customer' => 'sale',
                $row->from_location_type === 'customer' && $row->to_location_type === 'rep' => 'return',
                $row->from_location_type === 'rep' && $row->to_location_type === 'rep' => 'transfer_rep',
                $row->from_location_type === 'warehouse' && $row->to_location_type === 'warehouse' => 'transfer_wh',
                default => 'other',
            };
        }
        return match($row->txn_type_code) {
            'PURCHASE_RECEIPT' => 'purchase', 'ISSUE_ORDER' => 'load',
            'SALES_INVOICE' => 'sale', 'SALES_RETURN' => 'return',
            'PURCHASE_RETURN' => 'purchase_return', 'RETURN' => 'return',
            'WAREHOUSE_TRANSFER_IN' => 'transfer_wh_in', 'WAREHOUSE_TRANSFER_OUT' => 'transfer_wh_out',
            'REP_SALE' => 'sale', 'REP_RETURN' => 'return',
            'TRANSFER_TO_REP' => 'load', 'TRANSFER_FROM_REP' => 'unload',
            default => 'other',
        };
    };

    $resolveRefNumber = function($row) {
        if (!$row->reference_type || !$row->reference_id) return $row->transaction_no ?? '';
        try {
            $ref = $row->reference_type::find($row->reference_id);
            if (!$ref) return $row->transaction_no ?? '';
            $field = match(true) {
                str_contains($row->reference_type, 'SalesInvoice') => 'invoice_no',
                str_contains($row->reference_type, 'IssueOrder') => 'issue_no',
                str_contains($row->reference_type, 'ReturnOrder') => 'return_no',
                str_contains($row->reference_type, 'Purchase') => 'receipt_no',
                default => null,
            };
            return $field && isset($ref->{$field}) ? $ref->{$field} : "#{$row->reference_id}";
        } catch (\Exception $e) {
            return $row->transaction_no ?? '';
        }
    };

    $movements = [];
    $repMap = [];

    foreach ($rows as $row) {
        $type = $classifyMovement($row);
        $qty = (float) $row->qty;

        $fromName = $row->from_location_type && $row->from_location_id
            ? $resolveLocation($row->from_location_type, $row->from_location_id)
            : ($row->warehouse_id
                ? (\App\Models\Warehouse::find($row->warehouse_id)?->name ?? "مخزن #{$row->warehouse_id}")
                : '—');

        $toName = $row->to_location_type && $row->to_location_id
            ? $resolveLocation($row->to_location_type, $row->to_location_id)
            : '—';

        $movements[] = [
            'id' => $row->id,
            'transaction_date' => $row->transaction_date,
            'transaction_no' => $row->transaction_no,
            'ref_number' => $resolveRefNumber($row),
            'movement_type' => $type,
            'type_label' => $typeLabelMap[$type] ?? $row->txn_type_name ?? 'أخرى',
            'txn_type_code' => $row->txn_type_code,
            'txn_type_name' => $row->txn_type_name,
            'from_name' => $fromName,
            'to_name' => $toName,
            'qty' => $qty,
            'in_qty' => $qty > 0 ? $qty : 0,
            'out_qty' => $qty < 0 ? abs($qty) : 0,
            'unit_cost' => (float) $row->unit_cost,
            'total_cost' => (float) $row->total_cost,
            'notes' => $row->txn_notes ?? '',
        ];

        // Build rep balances
        $repIdVal = null;
        $repQty = 0;
        if ($row->from_location_type === 'rep') {
            $repIdVal = (int) $row->from_location_id;
            $repQty = -$qty;
        } elseif ($row->to_location_type === 'rep') {
            $repIdVal = (int) $row->to_location_id;
            $repQty = $qty;
        } elseif ($row->txn_type_code === 'ISSUE_ORDER' && str_contains($row->reference_type ?? '', 'IssueOrder')) {
            try {
                $ref = $row->reference_type::find($row->reference_id);
                if ($ref && $ref->employee_id) {
                    $repIdVal = (int) $ref->employee_id;
                    $repQty = abs($qty);
                }
            } catch (\Exception $e) {}
        }

        if ($repIdVal) {
            if (!isset($repMap[$repIdVal])) {
                $repMap[$repIdVal] = ['rep_id' => $repIdVal, 'loaded' => 0, 'sold' => 0, 'returned' => 0, 'unloaded' => 0, 'balance' => 0];
            }
            $repMap[$repIdVal]['balance'] += $repQty;
            if ($type === 'load') $repMap[$repIdVal]['loaded'] += abs($repQty);
            if ($type === 'sale') $repMap[$repIdVal]['sold'] += abs($repQty);
            if ($type === 'return') $repMap[$repIdVal]['returned'] += abs($repQty);
            if ($type === 'unload') $repMap[$repIdVal]['unloaded'] += abs($repQty);
        }
    }

    // Resolve rep names
    $repIds = array_keys($repMap);
    if (!empty($repIds)) {
        $emps = \App\Models\Employee::whereIn('id', $repIds)->get()->keyBy('id');
        foreach ($repMap as $id => &$data) {
            $data['rep_name'] = $emps->get($id)?->full_name_ar ?? "مندوب #$id";
        }
    }
    unset($data);

    $stats = [
        'total_purchase' => 0, 'total_load' => 0, 'total_sale' => 0,
        'total_return' => 0, 'total_unload' => 0, 'current_balance' => 0,
    ];
    foreach ($movements as $m) {
        $stats['current_balance'] += $m['qty'];
        match($m['movement_type']) {
            'purchase', 'purchase_return' => $stats['total_purchase'] += $m['out_qty'] ?: $m['in_qty'],
            'load' => $stats['total_load'] += $m['out_qty'] ?: $m['in_qty'],
            'sale' => $stats['total_sale'] += $m['out_qty'] ?: $m['in_qty'],
            'return' => $stats['total_return'] += $m['in_qty'] ?: $m['out_qty'],
            'unload' => $stats['total_unload'] += $m['in_qty'] ?: $m['out_qty'],
            default => null,
        };
    }

    return response()->json([
        'item' => [
            'id' => $item->id,
            'code' => $item->code ?? $item->id,
            'name_ar' => $item->name_ar ?? $item->name,
            'name_en' => $item->name_en ?? '',
            'unit' => $item->baseUnit?->name_ar ?? 'وحدة',
        ],
        'stats' => $stats,
        'movements' => $movements,
        'rep_balances' => array_values($repMap),
    ]);
})->middleware('auth:sanctum');

// Split Route Files
require __DIR__.'/api/auth.php';
require __DIR__.'/api/companies.php';
require __DIR__.'/api/branches.php';
require __DIR__.'/api/users.php';
require __DIR__.'/api/user_types.php';
require __DIR__.'/api/accounts.php';
require __DIR__.'/api/customers.php';
require __DIR__.'/api/suppliers.php';
require __DIR__.'/api/products.php';
require __DIR__.'/api/categories.php';
require __DIR__.'/api/sub-categories.php';
require __DIR__.'/api/brands.php';
require __DIR__.'/api/units.php';
require __DIR__.'/api/warehouses.php';
require __DIR__.'/api/locations.php';
require __DIR__.'/api/price_lists.php';
require __DIR__.'/api/safes.php';
require __DIR__.'/api/currencies.php';
require __DIR__.'/api/payment-methods.php';
require __DIR__.'/api/countries.php';
require __DIR__.'/api/states.php';
require __DIR__.'/api/governorates.php';
require __DIR__.'/api/cities.php';
require __DIR__.'/api/districts.php';
require __DIR__.'/api/streets.php';
require __DIR__.'/api/subscription-plans.php';
require __DIR__.'/api/company-subscriptions.php';
require __DIR__.'/api/company-subscription-limits.php';
require __DIR__.'/api/handheld.php';

// ===== Permissions & Roles Custom Routes =====
Route::get('permissions/matrix', [\App\Http\Controllers\Api\PermissionController::class, 'matrix']);
Route::get('permissions/check/{permission}', [\App\Http\Controllers\Api\PermissionController::class, 'check']);
Route::post('permissions/check-batch', [\App\Http\Controllers\Api\PermissionController::class, 'checkBatch']);
Route::post('roles/{role}/permissions', [\App\Http\Controllers\Api\RoleController::class, 'updatePermissions']);
Route::post('roles/copy-permissions', [\App\Http\Controllers\Api\RoleController::class, 'copyPermissions']);

// Per-company next-code routes
Route::get('purchase-invoices/next-code', function (\Illuminate\Http\Request $request) {
    $companyId = $request->input('company_id');
    $query = \App\Models\PurchaseInvoice::withTrashed();
    if ($companyId) {
        $query->where('company_id', $companyId);
    }
    $last = $query->latest('invoice_no')->first();
    $next = 1;
    if ($last && preg_match('/^PINV-(\d+)$/', $last->invoice_no, $m)) $next = intval($m[1]) + 1;
    return response()->json(['code' => 'PINV-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
});

Route::get('sales-invoices/next-code', function (\Illuminate\Http\Request $request) {
    $companyId = $request->input('company_id');
    $query = \App\Models\SalesInvoice::withTrashed();
    if ($companyId) {
        $query->where('company_id', $companyId);
    }
    $last = $query->latest('invoice_no')->first();
    $next = 1;
    if ($last && preg_match('/^SINV-(\d+)$/', $last->invoice_no, $m)) $next = intval($m[1]) + 1;
    return response()->json(['code' => 'SINV-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
});

// Resource Routes
$resources = [
    'roles' => \App\Http\Controllers\Api\RoleController::class,
    'permissions' => \App\Http\Controllers\Api\PermissionController::class,
    'warehouse-types' => \App\Http\Controllers\Api\WarehouseTypeController::class,
    'treasury-types' => \App\Http\Controllers\Api\TreasuryTypeController::class,
    'organizational-levels' => \App\Http\Controllers\Api\OrganizationalLevelController::class,
    'organization-unit-types' => \App\Http\Controllers\Api\OrganizationUnitTypeController::class,
    'organization-units' => \App\Http\Controllers\Api\OrganizationUnitController::class,
    'cost-center-types' => \App\Http\Controllers\Api\CostCenterTypeController::class,
    'cost-centers' => \App\Http\Controllers\Api\CostCenterController::class,
    'sales-territory-types' => \App\Http\Controllers\Api\SalesTerritoryTypeController::class,
    'sales-territories' => \App\Http\Controllers\Api\SalesTerritoryController::class,
    'departments' => \App\Http\Controllers\Api\DepartmentController::class,
    'position-levels' => \App\Http\Controllers\Api\PositionLevelController::class,
    'job-positions' => \App\Http\Controllers\Api\JobPositionController::class,
    'job-families' => \App\Http\Controllers\Api\JobFamilyController::class,
    'job-titles' => \App\Http\Controllers\Api\JobTitleController::class,
    'job-grades' => \App\Http\Controllers\Api\JobGradeController::class,
    'salary-scales' => \App\Http\Controllers\Api\SalaryScaleController::class,
    'employee-statuses' => \App\Http\Controllers\Api\EmployeeStatusController::class,
    'employees' => \App\Http\Controllers\Api\EmployeeController::class,
    'employee-assignments' => \App\Http\Controllers\Api\EmployeeAssignmentController::class,
    'contract-types' => \App\Http\Controllers\Api\ContractTypeController::class,
    'contract-statuses' => \App\Http\Controllers\Api\ContractStatusController::class,
    'employee-contracts' => \App\Http\Controllers\Api\EmployeeContractController::class,
    'employee-contract-amendments' => \App\Http\Controllers\Api\EmployeeContractAmendmentController::class,
    'leave-types' => \App\Http\Controllers\Api\LeaveTypeController::class,
    'leave-requests' => \App\Http\Controllers\Api\LeaveRequestController::class,
    'employee-loans' => \App\Http\Controllers\Api\EmployeeLoanController::class,
    'employee-advances' => \App\Http\Controllers\Api\EmployeeAdvanceController::class,
    'employee-penalties' => \App\Http\Controllers\Api\EmployeePenaltyController::class,
    'employee-rewards' => \App\Http\Controllers\Api\EmployeeRewardController::class,
    'shift-types' => \App\Http\Controllers\Api\ShiftTypeController::class,
    'shifts' => \App\Http\Controllers\Api\ShiftController::class,
    'employee-shifts' => \App\Http\Controllers\Api\EmployeeShiftController::class,
    'attendance-statuses' => \App\Http\Controllers\Api\AttendanceStatusController::class,
    'attendance-records' => \App\Http\Controllers\Api\AttendanceRecordController::class,
    'attendance-adjustments' => \App\Http\Controllers\Api\AttendanceAdjustmentController::class,
    'holidays' => \App\Http\Controllers\Api\HolidayController::class,
    'employee-missions' => \App\Http\Controllers\Api\EmployeeMissionController::class,
    'salary-component-types' => \App\Http\Controllers\Api\SalaryComponentTypeController::class,
    'salary-components' => \App\Http\Controllers\Api\SalaryComponentController::class,
    'employee-salary-structures' => \App\Http\Controllers\Api\EmployeeSalaryStructureController::class,
    'payroll-periods' => \App\Http\Controllers\Api\PayrollPeriodController::class,
    'payroll-runs' => \App\Http\Controllers\Api\PayrollRunController::class,
    'payroll-run-details' => \App\Http\Controllers\Api\PayrollRunDetailController::class,
    'customer-groups' => \App\Http\Controllers\Api\CustomerGroupController::class,
    'customer-classes' => \App\Http\Controllers\Api\CustomerClassController::class,
    'customer-types' => \App\Http\Controllers\Api\CustomerTypeController::class,
    'customer-account-types' => \App\Http\Controllers\Api\CustomerAccountTypeController::class,
    'trade-program-types' => \App\Http\Controllers\Api\TradeProgramTypeController::class,
    'customer-addresses' => \App\Http\Controllers\Api\CustomerAddressController::class,
    'customer-contacts' => \App\Http\Controllers\Api\CustomerContactController::class,
    'customer-credit-limits' => \App\Http\Controllers\Api\CustomerCreditLimitController::class,
    'item-categories' => \App\Http\Controllers\Api\ItemCategoryController::class,
    'item-sub-categories' => \App\Http\Controllers\Api\ItemSubCategoryController::class,
    'item-units' => \App\Http\Controllers\Api\ItemUnitController::class,
    'item-prices' => \App\Http\Controllers\Api\ItemPriceController::class,
    'item-barcodes' => \App\Http\Controllers\Api\ItemBarcodeController::class,
    'item-batches' => \App\Http\Controllers\Api\ItemBatchController::class,
    'items' => \App\Http\Controllers\Api\ItemController::class,
    'product-companies' => \App\Http\Controllers\Api\ProductCompanyController::class,
    'price-levels' => \App\Http\Controllers\Api\PriceLevelController::class,
    'customer-price-levels' => \App\Http\Controllers\Api\CustomerPriceLevelController::class,
    'customer-special-prices' => \App\Http\Controllers\Api\CustomerSpecialPriceController::class,
    'pricing-methods' => \App\Http\Controllers\Api\PricingMethodController::class,
    'pricing-rules' => \App\Http\Controllers\Api\PricingRuleController::class,
    'pricing-rule-conditions' => \App\Http\Controllers\Api\PricingRuleConditionController::class,
    'pricing-rule-items' => \App\Http\Controllers\Api\PricingRuleItemController::class,
    'quantity-price-breaks' => \App\Http\Controllers\Api\QuantityPriceBreakController::class,
    'contract-prices' => \App\Http\Controllers\Api\ContractPriceController::class,
    'pricing-calculations' => \App\Http\Controllers\Api\PricingCalculationController::class,
    'pricing-calculation-details' => \App\Http\Controllers\Api\PricingCalculationDetailController::class,
    'price-approval-requests' => \App\Http\Controllers\Api\PriceApprovalRequestController::class,
    'price-approval-steps' => \App\Http\Controllers\Api\PriceApprovalStepController::class,
    'pricing-exceptions' => \App\Http\Controllers\Api\PricingExceptionController::class,
    'pricing-audit-log' => \App\Http\Controllers\Api\PricingAuditLogController::class,
    'customer-price-lists' => \App\Http\Controllers\Api\CustomerPriceListController::class,
    'inventory-transaction-types' => \App\Http\Controllers\Api\InventoryTransactionTypeController::class,
    'inventory-transactions' => \App\Http\Controllers\Api\InventoryTransactionController::class,
    'inventory-transaction-items' => \App\Http\Controllers\Api\InventoryTransactionItemController::class,
    'inventory-opening-balances' => \App\Http\Controllers\Api\InventoryOpeningBalanceController::class,
    'stock-adjustments' => \App\Http\Controllers\Api\StockAdjustmentController::class,
    'stock-adjustment-items' => \App\Http\Controllers\Api\StockAdjustmentItemController::class,
    'stock-counts' => \App\Http\Controllers\Api\StockCountController::class,
    'stock-count-items' => \App\Http\Controllers\Api\StockCountItemController::class,
    'warehouse-transfers' => \App\Http\Controllers\Api\WarehouseTransferController::class,
    'warehouse-transfer-items' => \App\Http\Controllers\Api\WarehouseTransferItemController::class,
    'inventory-revaluations' => \App\Http\Controllers\Api\InventoryRevaluationController::class,
    'inventory-revaluation-items' => \App\Http\Controllers\Api\InventoryRevaluationItemController::class,
    'load-requests' => \App\Http\Controllers\Api\LoadRequestController::class,
    'load-request-items' => \App\Http\Controllers\Api\LoadRequestItemController::class,
    'issue-orders' => \App\Http\Controllers\Api\IssueOrderController::class,
    'issue-order-items' => \App\Http\Controllers\Api\IssueOrderItemController::class,
    'return-orders' => \App\Http\Controllers\Api\ReturnOrderController::class,
    'return-order-items' => \App\Http\Controllers\Api\ReturnOrderItemController::class,
    'distribution-plans' => \App\Http\Controllers\Api\DistributionPlanController::class,
    'salesman-assignments' => \App\Http\Controllers\Api\SalesmanAssignmentController::class,
    'sales-routes' => \App\Http\Controllers\Api\SalesRouteController::class,
    'route-schedules' => \App\Http\Controllers\Api\RouteScheduleController::class,
    'route-customers' => \App\Http\Controllers\Api\RouteCustomerController::class,
    'customer-visits' => \App\Http\Controllers\Api\CustomerVisitController::class,
    'route-visits' => \App\Http\Controllers\Api\RouteVisitController::class,
    'sales-incentives' => \App\Http\Controllers\Api\SalesIncentiveController::class,
    'sales-incentive-conditions' => \App\Http\Controllers\Api\SalesIncentiveConditionController::class,
    'sales-incentive-condition-items' => \App\Http\Controllers\Api\SalesIncentiveConditionItemController::class,
    'sales-incentive-rewards' => \App\Http\Controllers\Api\SalesIncentiveRewardController::class,
    'sales-invoices' => \App\Http\Controllers\Api\SalesInvoiceController::class,
    'sales-invoice-items' => \App\Http\Controllers\Api\SalesInvoiceItemController::class,
    'sales-invoice-discounts' => \App\Http\Controllers\Api\SalesInvoiceDiscountController::class,
    'sales-invoice-taxes' => \App\Http\Controllers\Api\SalesInvoiceTaxController::class,
    'sales-invoice-incentives' => \App\Http\Controllers\Api\SalesInvoiceIncentiveController::class,
    'collections' => \App\Http\Controllers\Api\CollectionController::class,
    'salesman-settlements' => \App\Http\Controllers\Api\SalesmanSettlementController::class,
    'customer-returns' => \App\Http\Controllers\Api\CustomerReturnController::class,
    'customer-return-items' => \App\Http\Controllers\Api\CustomerReturnItemController::class,
    'daily-distribution-dashboards' => \App\Http\Controllers\Api\DailyDistributionDashboardController::class,
    'treasuries' => \App\Http\Controllers\Api\TreasuryController::class,
    'treasury-shifts' => \App\Http\Controllers\Api\TreasuryShiftController::class,
    'treasury-shift-transactions' => \App\Http\Controllers\Api\TreasuryShiftTransactionController::class,
    'treasury-counts' => \App\Http\Controllers\Api\TreasuryCountController::class,
    'treasury-count-details' => \App\Http\Controllers\Api\TreasuryCountDetailController::class,
    'treasury-transfers' => \App\Http\Controllers\Api\TreasuryTransferController::class,
    'treasury-adjustments' => \App\Http\Controllers\Api\TreasuryAdjustmentController::class,
    'treasury-opening-balances' => \App\Http\Controllers\Api\TreasuryOpeningBalanceController::class,
    'treasury-daily-closings' => \App\Http\Controllers\Api\TreasuryDailyClosingController::class,
    'treasury-closing-details' => \App\Http\Controllers\Api\TreasuryClosingDetailController::class,
    'treasury-custodies' => \App\Http\Controllers\Api\TreasuryCustodyController::class,
    'treasury-custody-transactions' => \App\Http\Controllers\Api\TreasuryCustodyTransactionController::class,
    'treasury-cash-limits' => \App\Http\Controllers\Api\TreasuryCashLimitController::class,
    'treasury-alerts' => \App\Http\Controllers\Api\TreasuryAlertController::class,
    'treasury-transactions' => \App\Http\Controllers\Api\TreasuryTransactionController::class,
    'account-types' => \App\Http\Controllers\Api\AccountTypeController::class,
    'account-groups' => \App\Http\Controllers\Api\AccountGroupController::class,
    'journal-entry-types' => \App\Http\Controllers\Api\JournalEntryTypeController::class,
    'journal-entries' => \App\Http\Controllers\Api\JournalEntryController::class,
    'journal-entry-lines' => \App\Http\Controllers\Api\JournalEntryLineController::class,
    'fiscal-years' => \App\Http\Controllers\Api\FiscalYearController::class,
    'accounting-periods' => \App\Http\Controllers\Api\AccountingPeriodController::class,
    'opening-balances' => \App\Http\Controllers\Api\OpeningBalanceController::class,
    'opening-balance-documents' => \App\Http\Controllers\Api\OpeningBalanceDocumentController::class,
    'manual-journal-entries' => \App\Http\Controllers\Api\ManualJournalEntryController::class,
    'manual-journal-entry-lines' => \App\Http\Controllers\Api\ManualJournalEntryLineController::class,
    'bank-accounts' => \App\Http\Controllers\Api\BankAccountController::class,
    'bank-transfers' => \App\Http\Controllers\Api\BankTransferController::class,
    'bank-reconciliations' => \App\Http\Controllers\Api\BankReconciliationController::class,
    'receipt-vouchers' => \App\Http\Controllers\Api\ReceiptVoucherController::class,
    'payment-vouchers' => \App\Http\Controllers\Api\PaymentVoucherController::class,
    'customer-ledger' => \App\Http\Controllers\Api\CustomerLedgerController::class,
    'supplier-ledger' => \App\Http\Controllers\Api\SupplierLedgerController::class,
    'tax-types' => \App\Http\Controllers\Api\TaxTypeController::class,
    'tax-rates' => \App\Http\Controllers\Api\TaxRateController::class,
    'tax-groups' => \App\Http\Controllers\Api\TaxGroupController::class,
    'tax-group-details' => \App\Http\Controllers\Api\TaxGroupDetailController::class,
    'tax-exemptions' => \App\Http\Controllers\Api\TaxExemptionController::class,
    'customer-tax-profiles' => \App\Http\Controllers\Api\CustomerTaxProfileController::class,
    'supplier-tax-profiles' => \App\Http\Controllers\Api\SupplierTaxProfileController::class,
    'item-tax-profiles' => \App\Http\Controllers\Api\ItemTaxProfileController::class,
    'tax-rules' => \App\Http\Controllers\Api\TaxRuleController::class,
    'tax-calculations' => \App\Http\Controllers\Api\TaxCalculationController::class,
    'tax-calculation-details' => \App\Http\Controllers\Api\TaxCalculationDetailController::class,
    'tax-jurisdictions' => \App\Http\Controllers\Api\TaxJurisdictionController::class,
    'tax-periods' => \App\Http\Controllers\Api\TaxPeriodController::class,
    'tax-returns' => \App\Http\Controllers\Api\TaxReturnController::class,
    'tax-return-details' => \App\Http\Controllers\Api\TaxReturnDetailController::class,
    'withholding-tax-certificates' => \App\Http\Controllers\Api\WithholdingTaxCertificateController::class,
    'purchase-requests' => \App\Http\Controllers\Api\PurchaseRequestController::class,
    'purchase-request-items' => \App\Http\Controllers\Api\PurchaseRequestItemController::class,
    'supplier-groups' => \App\Http\Controllers\Api\SupplierGroupController::class,
    'supplier-contacts' => \App\Http\Controllers\Api\SupplierContactController::class,
    'supplier-quotations' => \App\Http\Controllers\Api\SupplierQuotationController::class,
    'supplier-quotation-items' => \App\Http\Controllers\Api\SupplierQuotationItemController::class,
    'purchase-orders' => \App\Http\Controllers\Api\PurchaseOrderController::class,
    'purchase-order-items' => \App\Http\Controllers\Api\PurchaseOrderItemController::class,
    'purchase-receipts' => \App\Http\Controllers\Api\PurchaseReceiptController::class,
    'purchase-receipt-items' => \App\Http\Controllers\Api\PurchaseReceiptItemController::class,
    'purchase-invoices' => \App\Http\Controllers\Api\PurchaseInvoiceController::class,
    'purchase-invoice-items' => \App\Http\Controllers\Api\PurchaseInvoiceItemController::class,
    'purchase-returns' => \App\Http\Controllers\Api\PurchaseReturnController::class,
    'purchase-return-items' => \App\Http\Controllers\Api\PurchaseReturnItemController::class,
    'purchase-expenses' => \App\Http\Controllers\Api\PurchaseExpenseController::class,
    'driver' => \App\Http\Controllers\Api\DriverController::class,
    'drivers' => \App\Http\Controllers\Api\DriverController::class,
    'vehicle-assignments' => \App\Http\Controllers\Api\VehicleAssignmentController::class,
    'vehicle-fuel-transactions' => \App\Http\Controllers\Api\VehicleFuelTransactionController::class,
    'vehicle-maintenance' => \App\Http\Controllers\Api\VehicleMaintenanceController::class,
    'vehicle-expenses' => \App\Http\Controllers\Api\VehicleExpenseController::class,
    'vehicle-loadings' => \App\Http\Controllers\Api\VehicleLoadingController::class,
    'vehicle-warehouses' => \App\Http\Controllers\Api\VehicleWarehouseController::class,
    'vehicle-inventory-transactions' => \App\Http\Controllers\Api\VehicleInventoryTransactionController::class,
    'vehicle-stock-balances' => \App\Http\Controllers\Api\VehicleStockBalanceController::class,
    'vehicle-loads' => \App\Http\Controllers\Api\VehicleLoadController::class,
    'vehicle-load-items' => \App\Http\Controllers\Api\VehicleLoadItemController::class,
    'vehicle-unloads' => \App\Http\Controllers\Api\VehicleUnloadController::class,
    'vehicle-unload-items' => \App\Http\Controllers\Api\VehicleUnloadItemController::class,
    'vehicle-cash-accounts' => \App\Http\Controllers\Api\VehicleCashAccountController::class,
    'vehicle-cash-transactions' => \App\Http\Controllers\Api\VehicleCashTransactionController::class,
    'vehicle-daily-expenses' => \App\Http\Controllers\Api\VehicleDailyExpenseController::class,
    'vehicle-stock-counts' => \App\Http\Controllers\Api\VehicleStockCountController::class,
    'vehicle-stock-count-items' => \App\Http\Controllers\Api\VehicleStockCountItemController::class,
    'vehicle-settlements' => \App\Http\Controllers\Api\VehicleSettlementController::class,
    'vehicle-settlement-items' => \App\Http\Controllers\Api\VehicleSettlementItemController::class,
    'vehicle-deposits' => \App\Http\Controllers\Api\VehicleDepositController::class,
    'vehicle-documents' => \App\Http\Controllers\Api\VehicleDocumentController::class,
    'vehicle-ownership-history' => \App\Http\Controllers\Api\VehicleOwnershipHistoryController::class,
    'vehicle-meter-readings' => \App\Http\Controllers\Api\VehicleMeterReadingController::class,
    'vehicle-maintenance-plans' => \App\Http\Controllers\Api\VehicleMaintenancePlanController::class,
    'vehicle-work-orders' => \App\Http\Controllers\Api\VehicleWorkOrderController::class,
    'vehicle-work-order-items' => \App\Http\Controllers\Api\VehicleWorkOrderItemController::class,
    'vehicle-maintenance-parts' => \App\Http\Controllers\Api\VehicleMaintenancePartController::class,
    'vehicle-tires' => \App\Http\Controllers\Api\VehicleTireController::class,
    'vehicle-tire-movements' => \App\Http\Controllers\Api\VehicleTireMovementController::class,
    'vehicle-tire-inspections' => \App\Http\Controllers\Api\VehicleTireInspectionController::class,
    'vehicle-batteries' => \App\Http\Controllers\Api\VehicleBatteryController::class,
    'vehicle-fuel-cards' => \App\Http\Controllers\Api\VehicleFuelCardController::class,
    'vehicle-fuel-stations' => \App\Http\Controllers\Api\VehicleFuelStationController::class,
    'vehicle-fuel-prices' => \App\Http\Controllers\Api\VehicleFuelPriceController::class,
    'driver-licenses' => \App\Http\Controllers\Api\DriverLicenseController::class,
    'driver-training' => \App\Http\Controllers\Api\DriverTrainingController::class,
    'driver-violations' => \App\Http\Controllers\Api\DriverViolationController::class,
    'driver-medical-tests' => \App\Http\Controllers\Api\DriverMedicalTestController::class,
    'driver-behavior-scores' => \App\Http\Controllers\Api\DriverBehaviorScoreController::class,
    'vehicle-accidents' => \App\Http\Controllers\Api\VehicleAccidentController::class,
    'vehicle-insurance' => \App\Http\Controllers\Api\VehicleInsuranceController::class,
    'vehicle-insurance-claims' => \App\Http\Controllers\Api\VehicleInsuranceClaimController::class,
    'vehicle-reservations' => \App\Http\Controllers\Api\VehicleReservationController::class,
    'geofences' => \App\Http\Controllers\Api\GeofenceController::class,
    'vehicle-geofence-events' => \App\Http\Controllers\Api\VehicleGeofenceEventController::class,
    'vehicle-speed-violations' => \App\Http\Controllers\Api\VehicleSpeedViolationController::class,
    'vehicle-idle-time' => \App\Http\Controllers\Api\VehicleIdleTimeController::class,
    'vehicle-trip-history' => \App\Http\Controllers\Api\VehicleTripHistoryController::class,
    'vehicle-cost-analysis' => \App\Http\Controllers\Api\VehicleCostAnalysisController::class,
    'vehicle-alerts' => \App\Http\Controllers\Api\VehicleAlertController::class,
    'asset-categories' => \App\Http\Controllers\Api\AssetCategoryController::class,
    'assets' => \App\Http\Controllers\Api\AssetController::class,
    'asset-assignments' => \App\Http\Controllers\Api\AssetAssignmentController::class,
    'asset-depreciations' => \App\Http\Controllers\Api\AssetDepreciationController::class,
    'leads' => \App\Http\Controllers\Api\LeadController::class,
    'lead-activities' => \App\Http\Controllers\Api\LeadActivityController::class,
    'opportunities' => \App\Http\Controllers\Api\OpportunityController::class,
    'opportunity-stages' => \App\Http\Controllers\Api\OpportunityStageController::class,
    'document-categories' => \App\Http\Controllers\Api\DocumentCategoryController::class,
    'documents' => \App\Http\Controllers\Api\DocumentController::class,
    'audit-logs' => \App\Http\Controllers\Api\AuditLogController::class,
    'login-logs' => \App\Http\Controllers\Api\LoginLogController::class,
    'api-logs' => \App\Http\Controllers\Api\ApiLogController::class,
    'workflow-definitions' => \App\Http\Controllers\Api\WorkflowDefinitionController::class,
    'workflow-steps' => \App\Http\Controllers\Api\WorkflowStepController::class,
    'approval-requests' => \App\Http\Controllers\Api\ApprovalRequestController::class,
    'approval-actions' => \App\Http\Controllers\Api\ApprovalActionController::class,
    'notification-templates' => \App\Http\Controllers\Api\NotificationTemplateController::class,
    'notifications' => \App\Http\Controllers\Api\NotificationController::class,
    'notification-queue' => \App\Http\Controllers\Api\NotificationQueueController::class,
    'kpi-definitions' => \App\Http\Controllers\Api\KpiDefinitionController::class,
    'kpi-targets' => \App\Http\Controllers\Api\KpiTargetController::class,
    'kpi-results' => \App\Http\Controllers\Api\KpiResultController::class,
    'sales-targets' => \App\Http\Controllers\Api\SalesTargetController::class,
    'sales-target-details' => \App\Http\Controllers\Api\SalesTargetDetailController::class,
    'budgets' => \App\Http\Controllers\Api\BudgetController::class,
    'budget-lines' => \App\Http\Controllers\Api\BudgetLineController::class,
    'demand-forecasts' => \App\Http\Controllers\Api\DemandForecastController::class,
    'forecast-history' => \App\Http\Controllers\Api\ForecastHistoryController::class,
    'replenishment-rules' => \App\Http\Controllers\Api\ReplenishmentRuleController::class,
    'replenishment-suggestions' => \App\Http\Controllers\Api\ReplenishmentSuggestionController::class,
    'route-templates' => \App\Http\Controllers\Api\RouteTemplateController::class,
    'route-stops' => \App\Http\Controllers\Api\RouteStopController::class,
    'gps-tracking-sessions' => \App\Http\Controllers\Api\GpsTrackingSessionController::class,
    'gps-tracking-points' => \App\Http\Controllers\Api\GpsTrackingPointController::class,
    'e-invoice-providers' => \App\Http\Controllers\Api\EInvoiceProviderController::class,
    'e-invoice-transactions' => \App\Http\Controllers\Api\EInvoiceTransactionController::class,
    'message-templates' => \App\Http\Controllers\Api\MessageTemplateController::class,
    'message-logs' => \App\Http\Controllers\Api\MessageLogController::class,
    'sync-batches' => \App\Http\Controllers\Api\SyncBatchController::class,
    'sync-logs' => \App\Http\Controllers\Api\SyncLogController::class,
    'mobile-devices' => \App\Http\Controllers\Api\MobileDeviceController::class,
    'master-data-request-types' => \App\Http\Controllers\Api\MasterDataTypeController::class,
    'master-data-requests' => \App\Http\Controllers\Api\MasterDataRequestController::class,
    'master-data-request-steps' => \App\Http\Controllers\Api\MasterDataRequestStepController::class,
    'master-data-request-history' => \App\Http\Controllers\Api\MasterDataRequestHistoryController::class,
    'master-data-workflows' => \App\Http\Controllers\Api\MasterDataWorkflowController::class,
    'master-data-workflow-steps' => \App\Http\Controllers\Api\MasterDataWorkflowStepController::class,
    'customer-agreement-types' => \App\Http\Controllers\Api\CustomerAgreementTypeController::class,
    'customer-agreements' => \App\Http\Controllers\Api\CustomerAgreementController::class,
    'customer-agreement-items' => \App\Http\Controllers\Api\CustomerAgreementItemController::class,
    'marketing-support-types' => \App\Http\Controllers\Api\MarketingSupportTypeController::class,
    'customer-marketing-supports' => \App\Http\Controllers\Api\CustomerMarketingSupportController::class,
    'customer-rebate-rules' => \App\Http\Controllers\Api\CustomerRebateRuleController::class,
    'customer-agreement-targets' => \App\Http\Controllers\Api\CustomerAgreementTargetController::class,
    'customer-agreement-payments' => \App\Http\Controllers\Api\CustomerAgreementPaymentController::class,
    'customer-agreement-history' => \App\Http\Controllers\Api\CustomerAgreementHistoryController::class,
    'marketing-asset-categories' => \App\Http\Controllers\Api\MarketingAssetCategoryController::class,
    'marketing-assets' => \App\Http\Controllers\Api\MarketingAssetController::class,
    'customer-marketing-assets' => \App\Http\Controllers\Api\CustomerMarketingAssetController::class,
    'marketing-asset-movements' => \App\Http\Controllers\Api\MarketingAssetMovementController::class,
    'marketing-asset-maintenance' => \App\Http\Controllers\Api\MarketingAssetMaintenanceController::class,
    'merchandising-visits' => \App\Http\Controllers\Api\MerchandisingVisitController::class,
    'merchandising-checklists' => \App\Http\Controllers\Api\MerchandisingChecklistController::class,
    'merchandising-visit-details' => \App\Http\Controllers\Api\MerchandisingVisitDetailController::class,
    'merchandising-photos' => \App\Http\Controllers\Api\MerchandisingPhotoController::class,
    'marketing-materials' => \App\Http\Controllers\Api\MarketingMaterialController::class,
    'customer-marketing-materials' => \App\Http\Controllers\Api\CustomerMarketingMaterialController::class,
    'marketing-campaigns' => \App\Http\Controllers\Api\MarketingCampaignController::class,
    'marketing-campaign-customers' => \App\Http\Controllers\Api\MarketingCampaignCustomerController::class,
    'competitors' => \App\Http\Controllers\Api\CompetitorController::class,
    'competitor-brands' => \App\Http\Controllers\Api\CompetitorBrandController::class,
    'competitor-products' => \App\Http\Controllers\Api\CompetitorProductController::class,
    'competitor-price-surveys' => \App\Http\Controllers\Api\CompetitorPriceSurveyController::class,
    'competitor-price-survey-items' => \App\Http\Controllers\Api\CompetitorPriceSurveyItemController::class,
    'competitor-promotions' => \App\Http\Controllers\Api\CompetitorPromotionController::class,
    'competitor-promotion-items' => \App\Http\Controllers\Api\CompetitorPromotionItemController::class,
    'shelf-share-surveys' => \App\Http\Controllers\Api\ShelfShareSurveyController::class,
    'shelf-share-items' => \App\Http\Controllers\Api\ShelfShareItemController::class,
    'competitor-new-products' => \App\Http\Controllers\Api\CompetitorNewProductController::class,
    'market-issues' => \App\Http\Controllers\Api\MarketIssueController::class,
    'competitor-photos' => \App\Http\Controllers\Api\CompetitorPhotoController::class,
    'survey-categories' => \App\Http\Controllers\Api\SurveyCategoryController::class,
    'surveys' => \App\Http\Controllers\Api\SurveyController::class,
    'survey-questions' => \App\Http\Controllers\Api\SurveyQuestionController::class,
    'survey-question-options' => \App\Http\Controllers\Api\SurveyQuestionOptionController::class,
    'survey-question-rules' => \App\Http\Controllers\Api\SurveyQuestionRuleController::class,
    'survey-responses' => \App\Http\Controllers\Api\SurveyResponseController::class,
    'survey-response-answers' => \App\Http\Controllers\Api\SurveyResponseAnswerController::class,
    'survey-response-options' => \App\Http\Controllers\Api\SurveyResponseOptionController::class,
    'survey-response-photos' => \App\Http\Controllers\Api\SurveyResponsePhotoController::class,
    'survey-scoring-rules' => \App\Http\Controllers\Api\SurveyScoringRuleController::class,
    'survey-scores' => \App\Http\Controllers\Api\SurveyScoreController::class,
    'survey-assignments' => \App\Http\Controllers\Api\SurveyAssignmentController::class,
    'merchandising-standards' => \App\Http\Controllers\Api\MerchandisingStandardController::class,
    'merchandising-standard-items' => \App\Http\Controllers\Api\MerchandisingStandardItemController::class,
    'display-locations' => \App\Http\Controllers\Api\DisplayLocationController::class,
    'merchandising-audits' => \App\Http\Controllers\Api\MerchandisingAuditController::class,
    'merchandising-audit-details' => \App\Http\Controllers\Api\MerchandisingAuditDetailController::class,
    'shelf-audits' => \App\Http\Controllers\Api\ShelfAuditController::class,
    'shelf-audit-items' => \App\Http\Controllers\Api\ShelfAuditItemController::class,
    'competitor-shelf-items' => \App\Http\Controllers\Api\CompetitorShelfItemController::class,
    'availability-audits' => \App\Http\Controllers\Api\AvailabilityAuditController::class,
    'refrigerator-audits' => \App\Http\Controllers\Api\RefrigeratorAuditController::class,
    'posm-audits' => \App\Http\Controllers\Api\PosmAuditController::class,
    'merchandising-audit-photos' => \App\Http\Controllers\Api\MerchandisingAuditPhotoController::class,
    'merchandising-tasks' => \App\Http\Controllers\Api\MerchandisingTaskController::class,
    'merchandising-task-assignments' => \App\Http\Controllers\Api\MerchandisingTaskAssignmentController::class,
    'notification-types' => \App\Http\Controllers\Api\NotificationTypeController::class,
    'notification-channels' => \App\Http\Controllers\Api\NotificationChannelController::class,
    'notification-events' => \App\Http\Controllers\Api\NotificationEventController::class,
    'notification-rules' => \App\Http\Controllers\Api\NotificationRuleController::class,
    'notification-rule-recipients' => \App\Http\Controllers\Api\NotificationRuleRecipientController::class,
    'notification-recipients' => \App\Http\Controllers\Api\NotificationRecipientController::class,
    'notification-deliveries' => \App\Http\Controllers\Api\NotificationDeliveryController::class,
    'notification-preferences' => \App\Http\Controllers\Api\NotificationPreferenceController::class,
    'notification-groups' => \App\Http\Controllers\Api\NotificationGroupController::class,
    'notification-group-members' => \App\Http\Controllers\Api\NotificationGroupMemberController::class,
    'alert-rules' => \App\Http\Controllers\Api\AlertRuleController::class,
    'alerts' => \App\Http\Controllers\Api\AlertController::class,
    'alert-actions' => \App\Http\Controllers\Api\AlertActionController::class,
    'scheduled-notifications' => \App\Http\Controllers\Api\ScheduledNotificationController::class,
    'integration-providers' => \App\Http\Controllers\Api\IntegrationProviderController::class,
    'integration-accounts' => \App\Http\Controllers\Api\IntegrationAccountController::class,
    'integration-endpoints' => \App\Http\Controllers\Api\IntegrationEndpointController::class,
    'integration-events' => \App\Http\Controllers\Api\IntegrationEventController::class,
    'integration-event-subscriptions' => \App\Http\Controllers\Api\IntegrationEventSubscriptionController::class,
    'api-clients' => \App\Http\Controllers\Api\ApiClientController::class,
    'api-tokens' => \App\Http\Controllers\Api\ApiTokenController::class,
    'api-permissions' => \App\Http\Controllers\Api\ApiPermissionController::class,
    'webhook-endpoints' => \App\Http\Controllers\Api\WebhookEndpointController::class,
    'webhook-subscriptions' => \App\Http\Controllers\Api\WebhookSubscriptionController::class,
    'webhook-logs' => \App\Http\Controllers\Api\WebhookLogController::class,
    'api-request-logs' => \App\Http\Controllers\Api\ApiRequestLogController::class,
    'api-rate-limits' => \App\Http\Controllers\Api\ApiRateLimitController::class,
    'integration-jobs' => \App\Http\Controllers\Api\IntegrationJobController::class,
    'integration-job-runs' => \App\Http\Controllers\Api\IntegrationJobRunController::class,
    'external-documents' => \App\Http\Controllers\Api\ExternalDocumentController::class,
    'external-document-logs' => \App\Http\Controllers\Api\ExternalDocumentLogController::class,
    'integration-error-logs' => \App\Http\Controllers\Api\IntegrationErrorLogController::class,
    'api-audit-logs' => \App\Http\Controllers\Api\ApiAuditLogController::class,
    'workflow-types' => \App\Http\Controllers\Api\WorkflowTypeController::class,
    'workflows' => \App\Http\Controllers\Api\WorkflowController::class,
    'workflow-conditions' => \App\Http\Controllers\Api\WorkflowConditionController::class,
    'workflow-instances' => \App\Http\Controllers\Api\WorkflowInstanceController::class,
    'workflow-instance-steps' => \App\Http\Controllers\Api\WorkflowInstanceStepController::class,
    'workflow-delegations' => \App\Http\Controllers\Api\WorkflowDelegationController::class,
    'workflow-escalations' => \App\Http\Controllers\Api\WorkflowEscalationController::class,
    'workflow-notifications' => \App\Http\Controllers\Api\WorkflowNotificationController::class,
    'workflow-actions-log' => \App\Http\Controllers\Api\WorkflowActionLogController::class,
    'workflow-templates' => \App\Http\Controllers\Api\WorkflowTemplateController::class,
    'workflow-template-steps' => \App\Http\Controllers\Api\WorkflowTemplateStepController::class,
    'workflow-roles' => \App\Http\Controllers\Api\WorkflowRoleController::class,
    'workflow-sla-rules' => \App\Http\Controllers\Api\WorkflowSlaRuleController::class,
    'vehicle-types' => \App\Http\Controllers\Api\VehicleTypeController::class,
    'vehicles' => \App\Http\Controllers\Api\VehicleController::class,
];

Route::get('route-schedules/today-count', [\App\Http\Controllers\Api\RouteScheduleController::class, 'todayCount']);

foreach ($resources as $uri => $controller) {
    if (class_exists($controller)) {
        Route::resource($uri, $controller);
    }
}

Route::post('return-orders/{returnOrder}/approve', [\App\Http\Controllers\Api\ReturnOrderController::class, 'approve']);
Route::post('return-orders/{returnOrder}/reject', [\App\Http\Controllers\Api\ReturnOrderController::class, 'reject']);

Route::post('distribution-plans/{plan}/calculate', [\App\Http\Controllers\Api\DistributionPlanController::class, 'calculate']);
Route::post('distribution-plans/{plan}/approve', [\App\Http\Controllers\Api\DistributionPlanController::class, 'approve']);
Route::post('distribution-plans/{plan}/reopen', [\App\Http\Controllers\Api\DistributionPlanController::class, 'reopen']);
Route::put('distribution-plans/{plan}/customers/{customer}/qty', [\App\Http\Controllers\Api\DistributionPlanController::class, 'updateCustomerQty']);
Route::put('distribution-plans/{plan}/items/{item}/qty', [\App\Http\Controllers\Api\DistributionPlanController::class, 'updateItemQty']);

// Fix: vehicle-inventory-transaction-items has param name > 32 chars
if (class_exists(\App\Http\Controllers\Api\VehicleInventoryTransactionItemController::class, false)) {
    Route::resource('vehicle-inventory-transaction-items', \App\Http\Controllers\Api\VehicleInventoryTransactionItemController::class, [
        'parameters' => ['vehicle-inventory-transaction-items' => 'vi_item'],
    ]);
}

// ===== Restore & Force Delete Routes =====
$softDeleteResources = [
    'customers', 'companies', 'branches', 'users', 'roles', 'warehouses', 'warehouses-types',
    'treasuries', 'treasury-types', 'items', 'item-categories', 'item-sub-categories', 'units',
    'product-companies', 'employees', 'employee-contracts', 'employee-contract-amendments',
    'employee-loans', 'employee-advances', 'employee-penalties', 'employee-rewards',
    'departments', 'position-levels', 'job-positions', 'job-families', 'job-titles', 'job-grades',
    'salary-scales', 'employee-statuses', 'contract-types', 'contract-statuses',
    'leave-types', 'leave-requests', 'shift-types', 'shifts', 'employee-shifts',
    'attendance-statuses', 'attendance-records', 'attendance-adjustments', 'holidays', 'employee-missions',
    'salary-component-types', 'salary-components', 'employee-salary-structures',
    'payroll-periods', 'payroll-runs', 'payroll-run-details',
    'customer-groups', 'customer-classes', 'customer-types', 'customer-account-types', 'trade-program-types', 'item-categories', 'item-sub-categories',
    'product-companies', 'item-units', 'item-prices', 'item-barcodes',
    'price-levels', 'customer-price-levels', 'customer-special-prices',
    'pricing-rules', 'pricing-rule-conditions', 'pricing-rule-items', 'pricing-methods',
    'quantity-price-breaks', 'contract-prices', 'pricing-calculations', 'pricing-calculation-details',
    'price-approval-requests', 'price-approval-steps', 'pricing-exceptions', 'pricing-audit-log',
    'customer-price-lists', 'inventory-transaction-types', 'inventory-transactions', 'inventory-transaction-items',
    'inventory-opening-balances', 'stock-adjustments', 'stock-adjustment-items',
    'stock-counts', 'stock-count-items', 'warehouse-transfers', 'warehouse-transfer-items',
    'inventory-revaluations', 'inventory-revaluation-items',
    'load-requests', 'load-request-items', 'issue-orders', 'issue-order-items',
    'return-orders', 'return-order-items',
    'salesman-assignments', 'sales-routes', 'route-schedules', 'route-customers',
    'customer-visits', 'route-visits', 'sales-incentives', 'sales-incentive-conditions',
    'sales-incentive-condition-items', 'sales-incentive-rewards',
    'sales-invoices', 'sales-invoice-items', 'sales-invoice-discounts', 'sales-invoice-taxes', 'sales-invoice-incentives',
    'collections', 'salesman-settlements', 'customer-returns', 'customer-return-items',
    'account-types', 'account-groups', 'accounts', 'journal-entry-types',
    'journal-entries', 'journal-entry-lines', 'fiscal-years', 'accounting-periods',
    'opening-balances', 'opening-balance-documents', 'manual-journal-entries', 'manual-journal-entry-lines',
    'bank-accounts', 'bank-transfers', 'bank-reconciliations',
    'receipt-vouchers', 'payment-vouchers', 'customer-ledger', 'supplier-ledger',
    'tax-types', 'tax-rates', 'tax-groups', 'tax-group-details', 'tax-exemptions',
    'customer-tax-profiles', 'supplier-tax-profiles', 'item-tax-profiles',
    'tax-rules', 'tax-calculations', 'tax-calculation-details',
    'tax-jurisdictions', 'tax-periods', 'tax-returns', 'tax-return-details',
    'withholding-tax-certificates',
    'purchase-requests', 'purchase-request-items', 'supplier-groups', 'supplier-contacts',
    'suppliers', 'supplier-quotations', 'supplier-quotation-items',
    'purchase-orders', 'purchase-order-items', 'purchase-receipts', 'purchase-receipt-items',
    'purchase-invoices', 'purchase-invoice-items', 'purchase-returns', 'purchase-return-items',
    'purchase-expenses',
    'drivers', 'vehicles', 'vehicle-types', 'vehicle-assignments', 'vehicle-fuel-transactions',
    'vehicle-maintenance', 'vehicle-expenses', 'vehicle-loadings',
    'vehicle-warehouses', 'vehicle-inventory-transactions', 'vehicle-inventory-transaction-items',
    'vehicle-stock-balances', 'vehicle-loads', 'vehicle-load-items',
    'vehicle-unloads', 'vehicle-unload-items', 'vehicle-cash-accounts', 'vehicle-cash-transactions',
    'vehicle-daily-expenses', 'vehicle-stock-counts', 'vehicle-stock-count-items',
    'vehicle-settlements', 'vehicle-settlement-items', 'vehicle-deposits',
    'vehicle-documents', 'vehicle-ownership-history', 'vehicle-meter-readings',
    'vehicle-maintenance-plans', 'vehicle-work-orders', 'vehicle-work-order-items',
    'vehicle-tires', 'vehicle-tire-movements', 'vehicle-tire-inspections',
    'vehicle-batteries', 'vehicle-fuel-cards', 'vehicle-fuel-stations', 'vehicle-fuel-prices',
    'driver-licenses', 'driver-training', 'driver-violations', 'driver-medical-tests',
    'vehicle-accidents', 'vehicle-insurance', 'vehicle-insurance-claims',
    'vehicle-reservations', 'geofences', 'vehicle-geofence-events',
    'vehicle-speed-violations', 'vehicle-idle-time', 'vehicle-trip-history',
    'vehicle-cost-analysis', 'vehicle-alerts',
    'asset-categories', 'assets', 'asset-assignments', 'asset-depreciations',
    'leads', 'lead-activities', 'opportunities', 'opportunity-stages',
    'document-categories', 'documents',
    'subscription-plans', 'company-subscriptions', 'company-subscription-limits',
    'payment-methods', 'currencies', 'countries', 'governorates', 'cities', 'districts', 'streets',
];

// Map resource names to their permission prefix for restore/force-delete
$restorePermMap = [
    'customers' => 'customer.customer',
    'suppliers' => 'purchase.supplier',
    'items' => 'inventory.item',
    'item-categories' => 'inventory.category',
    'item-sub-categories' => 'inventory.sub_category',
    'units' => 'inventory.unit',
    'warehouses' => 'inventory.warehouse',
    'employees' => 'hr.employee',
    'roles' => 'settings.role',
    'branches' => 'settings.branch',
    'users' => 'settings.user',
    'treasuries' => 'treasury.treasury',
    'accounts' => 'accounting.account',
    'payment-vouchers' => 'treasury.payment',
    'receipt-vouchers' => 'treasury.receipt',
    'sales-invoices' => 'sales.invoice',
    'purchase-invoices' => 'purchase.invoice',
    'journal-entries' => 'accounting.journal',
    'opening-balance-documents' => 'accounting.opening',
    'leads' => 'crm.lead',
    'opportunities' => 'crm.opportunity',
    'vehicles' => 'vehicle.vehicle',
    'drivers' => 'vehicle.driver',
    'assets' => 'asset.asset',
    'asset-categories' => 'asset.category',
    'customer-groups' => 'customer.group',
    'customer-classes' => 'customer.class',
    'customer-types' => 'customer.type',
    'supplier-groups' => 'supplier.group',
    'supplier-contacts' => 'supplier.contact',
    'stock-adjustments' => 'inventory.stock_adjustment',
    'stock-counts' => 'inventory.stock_count',
    'load-requests' => 'distribution.load_request',
    'issue-orders' => 'distribution.issue_order',
    'purchase-requests' => 'purchase.request',
    'purchase-orders' => 'purchase.order',
    'purchase-receipts' => 'purchase.receipt',
    'purchase-returns' => 'purchase.return',
    'customer-returns' => 'sales.return',
    'payroll-runs' => 'hr.payroll',
    'leave-requests' => 'hr.leave',
    'employee-loans' => 'hr.loan',
    'employee-advances' => 'hr.advance',
    'employee-penalties' => 'hr.penalty',
    'employee-rewards' => 'hr.reward',
    'departments' => 'hr.department',
    'job-positions' => 'hr.job_position',
    'shifts' => 'hr.shift',
    'attendance-records' => 'hr.attendance',
    'salary-components' => 'hr.salary_component',
    'tax-types' => 'tax.type',
    'tax-rates' => 'tax.rate',
    'tax-groups' => 'tax.group',
    'tax-exemptions' => 'tax.exemption',
    'tax-rules' => 'tax.rule',
    'tax-returns' => 'tax.return',
    'countries' => 'settings.country',
    'governorates' => 'settings.governorate',
    'cities' => 'settings.city',
    'districts' => 'settings.district',
    'currencies' => 'settings.currency',
    'payment-methods' => 'settings.payment_method',
    'companies' => 'settings.company',
    'e-invoice-providers' => 'einvoice.provider',
    'pricing-rules' => 'pricing.rule',
    'marketing-campaigns' => 'marketing.campaign',
    'marketing-assets' => 'marketing.asset',
    'marketing-materials' => 'marketing.material',
    'surveys' => 'survey.survey',
    'merchandising-standards' => 'merchandising.standard',
    'competitors' => 'crm.competitor',
    'competitor-products' => 'crm.competitor_product',
];

foreach ($softDeleteResources as $resource) {
    $uri = $resource;
    $singular = $resource;
    $perm = $restorePermMap[$resource] ?? 'settings.company';

    Route::post("{$uri}/{id}/restore", function ($id) use ($singular, $resource) {
        $modelMap = [
            'customers' => \App\Models\Customer::class,
            'companies' => \App\Models\Company::class,
            'branches' => \App\Models\Branch::class,
            'users' => \App\Models\User::class,
            'warehouses' => \App\Models\Warehouse::class,
            'treasuries' => \App\Models\Treasury::class,
            'items' => \App\Models\Item::class,
            'item-categories' => \App\Models\ItemCategory::class,
            'item-sub-categories' => \App\Models\ItemSubCategory::class,
            'units' => \App\Models\Unit::class,
            'product-companies' => \App\Models\ProductCompany::class,
            'employees' => \App\Models\Employee::class,
            'roles' => \App\Models\Role::class,
            'suppliers' => \App\Models\Supplier::class,
            'supplier-groups' => \App\Models\SupplierGroup::class,
            'supplier-contacts' => \App\Models\SupplierContact::class,
            'customer-groups' => \App\Models\CustomerGroup::class,
            'customer-classes' => \App\Models\CustomerClass::class,
            'customer-types' => \App\Models\CustomerType::class,
            'drivers' => \App\Models\Driver::class,
            'vehicles' => \App\Models\Vehicle::class,
            'vehicle-types' => \App\Models\VehicleType::class,
            'vehicle-warehouses' => \App\Models\VehicleWarehouse::class,
            'load-requests' => \App\Models\LoadRequest::class,
            'load-request-items' => \App\Models\LoadRequestItem::class,
            'countries' => \App\Models\Country::class,
            'governorates' => \App\Models\Governorate::class,
            'cities' => \App\Models\City::class,
            'districts' => \App\Models\District::class,
            'streets' => \App\Models\Street::class,
            'opening-balance-documents' => \App\Models\OpeningBalanceDocument::class,
        ];
        $class = $modelMap[$resource] ?? null;
        if (!$class) return response()->json(['message' => 'Not supported'], 404);
        $model = $class::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    })->middleware("permission:{$perm}.restore");

    Route::delete("{$uri}/{id}/force-delete", function ($id) use ($resource) {
        $modelMap = [
            'customers' => \App\Models\Customer::class,
            'companies' => \App\Models\Company::class,
            'branches' => \App\Models\Branch::class,
            'users' => \App\Models\User::class,
            'warehouses' => \App\Models\Warehouse::class,
            'treasuries' => \App\Models\Treasury::class,
            'items' => \App\Models\Item::class,
            'item-categories' => \App\Models\ItemCategory::class,
            'item-sub-categories' => \App\Models\ItemSubCategory::class,
            'units' => \App\Models\Unit::class,
            'product-companies' => \App\Models\ProductCompany::class,
            'employees' => \App\Models\Employee::class,
            'roles' => \App\Models\Role::class,
            'suppliers' => \App\Models\Supplier::class,
            'supplier-groups' => \App\Models\SupplierGroup::class,
            'supplier-contacts' => \App\Models\SupplierContact::class,
            'customer-groups' => \App\Models\CustomerGroup::class,
            'customer-classes' => \App\Models\CustomerClass::class,
            'customer-types' => \App\Models\CustomerType::class,
            'drivers' => \App\Models\Driver::class,
            'vehicles' => \App\Models\Vehicle::class,
            'vehicle-types' => \App\Models\VehicleType::class,
            'vehicle-warehouses' => \App\Models\VehicleWarehouse::class,
            'load-requests' => \App\Models\LoadRequest::class,
            'load-request-items' => \App\Models\LoadRequestItem::class,
            'countries' => \App\Models\Country::class,
            'governorates' => \App\Models\Governorate::class,
            'cities' => \App\Models\City::class,
            'districts' => \App\Models\District::class,
            'streets' => \App\Models\Street::class,
            'opening-balance-documents' => \App\Models\OpeningBalanceDocument::class,
        ];
        $class = $modelMap[$resource] ?? null;
        if (!$class) return response()->json(['message' => 'Not supported'], 404);
        $class::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    })->middleware("permission:{$perm}.delete");
}

// ===== Permission Custom Routes =====
Route::get('permissions/matrix', [\App\Http\Controllers\Api\PermissionController::class, 'matrix']);
Route::get('permissions/check/{permission}', [\App\Http\Controllers\Api\PermissionController::class, 'check']);
Route::post('permissions/check-batch', [\App\Http\Controllers\Api\PermissionController::class, 'checkBatch']);

// ===== Role Custom Routes =====
Route::post('roles/{role}/permissions', [\App\Http\Controllers\Api\RoleController::class, 'updatePermissions']);
Route::post('roles/copy-permissions', [\App\Http\Controllers\Api\RoleController::class, 'copyPermissions']);

// ===== Opening Balance Document Custom Routes =====
Route::post('opening-balance-documents/{openingBalanceDocument}/post', [\App\Http\Controllers\Api\OpeningBalanceDocumentController::class, 'post'])->middleware('permission:accounting.opening.post');
Route::post('opening-balance-documents/{openingBalanceDocument}/cancel', [\App\Http\Controllers\Api\OpeningBalanceDocumentController::class, 'cancel'])->middleware('permission:accounting.opening.cancel');

// ===== Sales Invoice Custom Routes =====
Route::post('sales-invoices/{salesInvoice}/post', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'post'])->middleware('permission:sales.invoice.post');
Route::post('sales-invoices/{salesInvoice}/cancel', [\App\Http\Controllers\Api\SalesInvoiceController::class, 'cancel'])->middleware('permission:sales.invoice.cancel');

// ===== Purchase Invoice Custom Routes =====
Route::post('purchase-invoices/{purchaseInvoice}/post', [\App\Http\Controllers\Api\PurchaseInvoiceController::class, 'post'])->middleware('permission:purchase.invoice.post');
Route::post('purchase-invoices/{purchaseInvoice}/cancel', [\App\Http\Controllers\Api\PurchaseInvoiceController::class, 'cancel'])->middleware('permission:purchase.invoice.cancel');

// ===== Inventory Module =====

// Stock Balance Report
Route::get('stock-balances', function (\Illuminate\Http\Request $request) {
    $companyId = $request->input('company_id');
    $warehouseId = $request->input('warehouse_id');
    $search = $request->input('search');

    $warehouses = \App\Models\Warehouse::query()
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->where('is_active', true)
        ->get(['id', 'code', 'name', 'name_ar', 'name_en']);

    $items = \App\Models\Item::query()
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
            $q2->where('name_ar', 'like', "%$search%")
               ->orWhere('code', 'like', "%$search%")
               ->orWhere('barcode', 'like', "%$search%");
        }))
        ->with('baseUnit')
        ->get();

    $openingBalances = \App\Models\InventoryOpeningBalance::query()
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
        ->get()
        ->groupBy('item_id');

    $transactions = \App\Models\InventoryTransaction::query()
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
        ->where('status', 'posted')
        ->with(['transactionType', 'items'])
        ->get();

    $stockQty = [];
    foreach ($transactions as $txn) {
        foreach ($txn->items as $txnItem) {
            $itemId = $txnItem->item_id;
            $whId = $txn->warehouse_id;
            if (!isset($stockQty[$itemId])) $stockQty[$itemId] = [];
            if (!isset($stockQty[$itemId][$whId])) $stockQty[$itemId][$whId] = 0;
            // Qty already contains the sign (positive for additions, negative for subtractions)
            $stockQty[$itemId][$whId] += (float)$txnItem->qty;
        }
    }

    foreach ($openingBalances as $obItems) {
        foreach ($obItems as $obItem) {
            $itemId = $obItem->item_id;
            $whId = $obItem->warehouse_id;
            if (!isset($stockQty[$itemId])) $stockQty[$itemId] = [];
            if (!isset($stockQty[$itemId][$whId])) $stockQty[$itemId][$whId] = 0;
            $conversionFactor = 1;
            if (!empty($obItem->unit_id)) {
                $iu = \App\Models\ItemUnit::where('item_id', $itemId)
                    ->where('unit_id', $obItem->unit_id)->first();
                if ($iu && $iu->conversion_factor > 0) $conversionFactor = $iu->conversion_factor;
            }
            $stockQty[$itemId][$whId] += (float)$obItem->qty * $conversionFactor;
        }
    }

    if ($warehouseId) {
        $stockQty = array_filter($stockQty, fn($whStocks) => isset($whStocks[$warehouseId]));
    }

    $result = $items->map(function ($item) use ($stockQty, $warehouses, $transactions) {
        $itemStock = [];
        $totalQty = 0;
        foreach ($warehouses as $wh) {
            $qty = $stockQty[$item->id][$wh->id] ?? 0;
            $itemStock[$wh->id] = $qty;
            $totalQty += $qty;
        }

        $allItemUnits = \App\Models\ItemUnit::where('item_id', $item->id)
            ->whereNull('deleted_at')
            ->with('unit')
            ->get();

        $sortedUnits = $allItemUnits->sortByDesc('conversion_factor')->values();

        $unitBreakdown = [];
        $remaining = (int)floor($totalQty);
        foreach ($sortedUnits as $iu) {
            $cf = (int)floor((float)$iu->conversion_factor);
            $count = $cf > 0 ? intdiv($remaining, $cf) : 0;
            $remaining -= $count * $cf;
            $unitBreakdown[] = [
                'unit_id' => $iu->unit_id,
                'unit_name' => $iu->unit?->name_ar ?? '',
                'conversion_factor' => $cf,
                'stock' => $count,
            ];
        }

        $lastMovement = $transactions->filter(fn($txn) => $txn->items->contains('item_id', $item->id))
            ->sortByDesc('transaction_date')
            ->first();

        return [
            'id' => $item->id,
            'code' => $item->code,
            'barcode' => $item->barcode,
            'name_ar' => $item->name_ar,
            'name_en' => $item->name_en,
            'image' => $item->image,
            'total_qty' => $totalQty,
            'unit_count' => count($unitBreakdown),
            'unit_breakdown' => $unitBreakdown,
            'last_movement_date' => $lastMovement?->transaction_date?->format('Y-m-d H:i'),
            'last_movement_notes' => $lastMovement?->notes,
            'warehouses' => $itemStock,
        ];
    });

    return response()->json([
        'warehouses' => $warehouses,
        'items' => $result,
    ]);
});

// Loading Products - Items with stock in warehouse
Route::get('loading-products', function (\Illuminate\Http\Request $request) {
    $warehouseId = $request->input('warehouse_id');
    $companyId = $request->input('company_id');
    $search = $request->input('search');

    if (!$warehouseId) {
        return response()->json(['message' => 'warehouse_id مطلوب'], 400);
    }

    $openingBalances = \App\Models\InventoryOpeningBalance::query()
        ->where('warehouse_id', $warehouseId)
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->get()
        ->groupBy('item_id')
        ->map(fn($group) => $group->sum(fn($i) => (float)$i->qty))
        ->toArray();

    $transactions = \App\Models\InventoryTransaction::query()
        ->where('warehouse_id', $warehouseId)
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->where('status', 'posted')
        ->with('transactionType')
        ->get();

    $stockQty = [];
    foreach ($transactions as $txn) {
        foreach ($txn->items as $txnItem) {
            $itemId = $txnItem->item_id;
            if (!isset($stockQty[$itemId])) $stockQty[$itemId] = 0;
            // Qty already contains the sign (positive for additions, negative for subtractions)
            $stockQty[$itemId] += (float)$txnItem->qty;
        }
    }

    foreach ($openingBalances as $itemId => $qty) {
        if (!isset($stockQty[$itemId])) $stockQty[$itemId] = 0;
        $stockQty[$itemId] += $qty;
    }

    $itemIds = array_filter($stockQty, fn($qty) => $qty > 0);
    if (empty($itemIds)) {
        return response()->json(['warehouses' => [], 'items' => []]);
    }

    $items = \App\Models\Item::query()
        ->whereIn('id', array_keys($itemIds))
        ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
            $q2->where('name_ar', 'like', "%$search%")
               ->orWhere('code', 'like', "%$search%")
               ->orWhere('barcode', 'like', "%$search%");
        }))
        ->with('baseUnit')
        ->get();

    $warehouses = \App\Models\Warehouse::query()
        ->where('id', $warehouseId)
        ->get(['id', 'code', 'name', 'name_ar']);

    $batches = \App\Models\ItemBatch::query()
        ->whereIn('item_id', array_keys($itemIds))
        ->where('remaining_qty', '>', 0)
        ->get()
        ->groupBy('item_id');

    $result = $items->map(function ($item) use ($stockQty, $batches) {
        $expiry = null;
        if ($batches->has($item->id)) {
            $itemBatches = $batches[$item->id];
            $earliest = $itemBatches->where('expiry_date', '!=', null)->sortBy('expiry_date')->first();
            if ($earliest) {
                $expiry = $earliest->expiry_date?->format('Y-m-d');
            }
        }
        return [
            'id' => $item->id,
            'code' => $item->code,
            'barcode' => $item->barcode,
            'name_ar' => $item->name_ar,
            'name_en' => $item->name_en,
            'unit' => $item->baseUnit?->name_ar ?? $item->baseUnit?->name ?? null,
            'unit_id' => $item->base_unit_id,
            'stock_qty' => $stockQty[$item->id] ?? 0,
            'expiry_date' => $expiry,
        ];
    });

    return response()->json([
        'warehouses' => $warehouses,
        'items' => $result->values(),
    ]);
});

// Create Load Request
Route::post('load-requests/create', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'warehouse_id' => 'required|exists:warehouses,id',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.unit_id' => 'nullable|exists:units,id',
        'items.*.quantity' => 'required|numeric|min:0',
        'items.*.incentive_qty' => 'nullable|numeric|min:0',
        'items.*.expiry_date' => 'nullable|date',
        'notes' => 'nullable|string',
    ]);

    $loadRequest = \App\Models\LoadRequest::create([
        'company_id' => $request->input('company_id', 1),
        'branch_id' => $request->input('branch_id'),
        'warehouse_id' => $data['warehouse_id'],
        'employee_id' => $request->input('employee_id', 1),
        'request_date' => now()->toDateString(),
        'trip_date' => now()->toDateString(),
        'status' => 'draft',
        'notes' => $data['notes'] ?? null,
    ]);

    foreach ($data['items'] as $item) {
        $qty = (float)$item['quantity'];
        $incentiveQty = (float)($item['incentive_qty'] ?? 0);
        $totalQty = $qty + $incentiveQty;
        if ($totalQty > 0) {
            \App\Models\LoadRequestItem::create([
                'load_request_id' => $loadRequest->id,
                'item_id' => $item['item_id'],
                'unit_id' => $item['unit_id'] ?? null,
                'quantity' => $totalQty,
                'notes' => $incentiveQty > 0 ? "حافز: $incentiveQty" : null,
            ]);
        }
    }

    return response()->json($loadRequest->load('items.item'), 201);
});

// Update Load Request Status
Route::patch('load-requests/{loadRequest}/status', [\App\Http\Controllers\Api\LoadRequestController::class, 'updateStatus']);
Route::post('load-requests/{loadRequest}/approve', [\App\Http\Controllers\Api\LoadRequestController::class, 'approve']);
Route::post('load-requests/{loadRequest}/reject', [\App\Http\Controllers\Api\LoadRequestController::class, 'reject']);

// Warehouse Transfers next-code
Route::get('warehouse-transfers/next-code', function () {
    $last = \App\Models\WarehouseTransfer::orderByRaw("CAST(SUBSTR(code, 4) AS INTEGER)")->latest('code')->first();
    $next = 1;
    if ($last && preg_match('/^WT-(\d+)$/', $last->code, $m)) $next = intval($m[1]) + 1;
    return response()->json(['code' => 'WT-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
});

// ============================================================
// PHASE 5: REPORT BUILDER
// ============================================================
Route::get('reports/tables', [\App\Http\Controllers\Api\ReportController::class, 'tables']);
Route::get('reports/tables/{table}/schema', [\App\Http\Controllers\Api\ReportController::class, 'tableSchema']);
Route::get('reports/templates', [\App\Http\Controllers\Api\ReportController::class, 'templates']);
Route::get('reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
Route::post('reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);
Route::get('reports/{report}', [\App\Http\Controllers\Api\ReportController::class, 'show']);
Route::put('reports/{report}', [\App\Http\Controllers\Api\ReportController::class, 'update']);
Route::delete('reports/{report}', [\App\Http\Controllers\Api\ReportController::class, 'destroy']);
Route::post('reports/{report}/execute', [\App\Http\Controllers\Api\ReportController::class, 'execute']);
Route::post('reports/{report}/share', [\App\Http\Controllers\Api\ReportController::class, 'share']);

// ============================================================
// PHASE 6: INTEGRATION HUB
// ============================================================
$ic = \App\Http\Controllers\Api\IntegrationController::class;

// Webhooks
Route::get('webhooks', [$ic, 'webhookIndex']);
Route::post('webhooks', [$ic, 'webhookStore']);
Route::get('webhooks/{webhook}', [$ic, 'webhookShow']);
Route::put('webhooks/{webhook}', [$ic, 'webhookUpdate']);
Route::delete('webhooks/{webhook}', [$ic, 'webhookDestroy']);
Route::post('webhooks/{webhook}/test', [$ic, 'webhookTest']);
Route::get('webhooks/{webhook}/deliveries', [$ic, 'webhookDeliveries']);

// API Keys
Route::get('api-keys', [$ic, 'apiKeyIndex']);
Route::post('api-keys', [$ic, 'apiKeyStore']);
Route::delete('api-keys/{apiKey}', [$ic, 'apiKeyDestroy']);
Route::patch('api-keys/{apiKey}/toggle', [$ic, 'apiKeyToggle']);
Route::get('api-keys/{apiKey}/logs', [$ic, 'apiKeyLogs']);

// Available events for webhook registration
Route::get('webhooks/events/available', [$ic, 'availableEvents']);

// ============================================================
// PHASE 7: BACKGROUND JOBS MONITOR
// ============================================================
$mc = \App\Http\Controllers\Api\MonitoringController::class;
Route::get('monitoring/queue/stats', [$mc, 'queueStats']);
Route::get('monitoring/queue/jobs', [$mc, 'queueJobs']);
Route::post('monitoring/queue/jobs/{id}/retry', [$mc, 'queueRetry']);
Route::delete('monitoring/queue/clear', [$mc, 'queueClear']);
Route::get('monitoring/tasks', [$mc, 'tasks']);
Route::post('monitoring/tasks/{command}/run', [$mc, 'taskRun']);
Route::get('monitoring/health', [$mc, 'health']);
Route::get('monitoring/activity', [$mc, 'activity']);
Route::get('monitoring/activity/stats', [$mc, 'activityStats']);

// ============================================================
// PHASE 10: SUPER ADMIN PANEL
// ============================================================
$sc = \App\Http\Controllers\Api\SuperAdminController::class;
Route::get('super-admin/stats', [$sc, 'stats']);
Route::get('super-admin/health', [$sc, 'health']);
Route::get('super-admin/companies', [$sc, 'companies']);
Route::get('super-admin/companies/{company}', [$sc, 'companyShow']);
Route::put('super-admin/companies/{company}/subscription', [$sc, 'updateSubscription']);
Route::get('super-admin/plans', [$sc, 'plans']);

// ===== Vehicle Alert Custom Routes =====
Route::post('vehicle-alerts/{id}/mark-read', [\App\Http\Controllers\Api\VehicleAlertController::class, 'markAsRead']);
Route::post('vehicle-alerts/{id}/resolve', [\App\Http\Controllers\Api\VehicleAlertController::class, 'resolve']);

// ============================================================
// PHASE 8: API DOCUMENTATION (auto-generated)
// ============================================================
Route::get('docs/api', function () {
    $routes = \Illuminate\Support\Facades\Route::getRoutes()->getRoutes();
    $docs = [];
    foreach ($routes as $route) {
        if (!str_starts_with($route->uri(), 'api/')) continue;
        $docs[] = [
            'method' => $route->methods()[0],
            'path' => '/' . $route->uri(),
            'name' => $route->getName(),
            'middleware' => $route->gatherMiddleware(),
        ];
    }
    return response()->json(['data' => $docs, 'count' => count($docs)]);
});

Route::get('docs/modules', function () {
    $modules = \App\Services\ModuleRegistry::getInstalledModules();
    $result = [];
    foreach ($modules as $code => $module) {
        $result[] = [
            'code' => $code,
            'name' => $module['name'] ?? $code,
            'name_ar' => $module['name_ar'] ?? $code,
            'version' => $module['version'] ?? '1.0.0',
            'description' => $module['description'] ?? '',
            'permissions' => \App\Services\ModuleRegistry::getModulePermissions($code),
            'features' => \App\Services\ModuleRegistry::getModuleFeatures($code),
        ];
    }
    return response()->json(['data' => $result]);
});

}); // End auth:sanctum group
