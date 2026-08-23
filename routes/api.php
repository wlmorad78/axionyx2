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

// App Update (no auth required)
Route::get('app/version', [\App\Http\Controllers\Api\AppUpdate\AppVersionController::class, 'latest']);
Route::get('app/versions', [\App\Http\Controllers\Api\AppUpdate\AppVersionController::class, 'index']);
Route::post('app/versions', [\App\Http\Controllers\Api\AppUpdate\AppVersionController::class, 'store']);

// Postman collection
Route::get('postman-collection', function () {
    $path = storage_path('api-docs/postman_collection.json');
    if (! file_exists($path)) abort(404, 'Postman collection not found');
    return response()->download($path, 'Axionyx_ERP_API.postman_collection.json', ['Content-Type' => 'application/json']);
});

// Public: Login (no auth required)
Route::post('login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);

// Handheld2 API
require __DIR__.'/api/handheld2.php';

// Handheld Auth (no auth required)
require __DIR__.'/api/handheld_auth.php';

// Health check - no auth required
Route::get('handheld/health', function () {
    return response()->json(['success' => true, 'service' => 'api']);
});

// Protected: require auth for all non-login routes below
Route::middleware(['auth:sanctum', 'day-closing'])->group(function () {

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
Route::get('audit-logs/stats', [\App\Http\Controllers\Api\Integration\AuditLogController::class, 'stats']);
Route::apiResource('audit-logs', \App\Http\Controllers\Api\Integration\AuditLogController::class)->only(['index', 'show']);

// Notifications
Route::get('notifications/unread-count', [\App\Http\Controllers\Api\Notifications\NotificationController::class, 'unreadCount']);
Route::get('notifications/stats', [\App\Http\Controllers\Api\Notifications\NotificationController::class, 'stats']);
Route::put('notifications/read-all', [\App\Http\Controllers\Api\Notifications\NotificationController::class, 'markAllRead']);
Route::put('notifications/{notification}/read', [\App\Http\Controllers\Api\Notifications\NotificationController::class, 'markRead']);
Route::apiResource('notifications', \App\Http\Controllers\Api\Notifications\NotificationController::class)->only(['index', 'destroy']);

// Approvals
Route::get('approvals/stats', [\App\Http\Controllers\Api\Workflows\ApprovalController::class, 'stats']);
Route::post('approvals/{approval}/approve', [\App\Http\Controllers\Api\Workflows\ApprovalController::class, 'approve']);
Route::post('approvals/{approval}/reject', [\App\Http\Controllers\Api\Workflows\ApprovalController::class, 'reject']);
Route::apiResource('approvals', \App\Http\Controllers\Api\Workflows\ApprovalController::class)->only(['index', 'show']);

// Company settings (flexible key-value)
Route::get('company-settings', [\App\Http\Controllers\Api\Company\CompanySettingController::class, 'index']);
Route::get('company-settings/{group}', [\App\Http\Controllers\Api\Company\CompanySettingController::class, 'byGroup']);
Route::put('company-settings', [\App\Http\Controllers\Api\Company\CompanySettingController::class, 'update']);
Route::delete('company-settings/{group}/{key}', [\App\Http\Controllers\Api\Company\CompanySettingController::class, 'destroy']);

// Module Manager
Route::get('modules/manifest', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'manifest']);
Route::get('modules/{code}/permissions', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'permissions']);
Route::get('modules/{code}/menu', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'menu']);
Route::post('modules/{code}/install', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'install']);
Route::delete('modules/{code}/uninstall', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'uninstall']);
Route::put('modules/{code}/enable', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'enable']);
Route::put('modules/{code}/disable', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'disable']);
Route::post('modules/{code}/upgrade', [\App\Http\Controllers\Api\Permissions\ModuleController::class, 'upgrade']);
Route::apiResource('modules', \App\Http\Controllers\Api\Permissions\ModuleController::class)->only(['index', 'show']);

// Event Bus
Route::get('events/stats', [\App\Http\Controllers\Api\Notifications\EventController::class, 'stats']);
Route::get('events/history', [\App\Http\Controllers\Api\Notifications\EventController::class, 'history']);
Route::get('events/{code}/subscriptions', [\App\Http\Controllers\Api\Notifications\EventController::class, 'subscriptions']);
Route::post('events/{code}/subscribe', [\App\Http\Controllers\Api\Notifications\EventController::class, 'subscribe']);
Route::delete('events/{code}/unsubscribe', [\App\Http\Controllers\Api\Notifications\EventController::class, 'unsubscribe']);
Route::post('events/{code}/fire', [\App\Http\Controllers\Api\Notifications\EventController::class, 'fire']);
Route::apiResource('events', \App\Http\Controllers\Api\Notifications\EventController::class)->only(['index', 'show']);

// Resource next-code routes
Route::get('customers/next-code', [\App\Http\Controllers\Api\CRM\CustomerController::class, 'nextCode']);
Route::get('customer-groups/next-code', [\App\Http\Controllers\Api\CRM\CustomerGroupController::class, 'nextCode']);
Route::get('customer-classes/next-code', [\App\Http\Controllers\Api\CRM\CustomerClassController::class, 'nextCode']);
Route::get('customer-types/next-code', [\App\Http\Controllers\Api\CRM\CustomerTypeController::class, 'nextCode']);
Route::get('customer-account-types/next-code', [\App\Http\Controllers\Api\CRM\CustomerAccountTypeController::class, 'nextCode']);
Route::get('trade-program-types/next-code', [\App\Http\Controllers\Api\Sales\TradeProgramTypeController::class, 'nextCode']);
Route::get('warehouses/next-code', [\App\Http\Controllers\Api\Inventory\WarehouseController::class, 'nextCode']);
Route::get('companies/next-code', [\App\Http\Controllers\Api\Company\CompanyController::class, 'nextCode']);
Route::get('branches/next-code', [\App\Http\Controllers\Api\Settings\BranchController::class, 'nextCode']);
Route::get('employees/next-code', [\App\Http\Controllers\Api\HR\EmployeeController::class, 'nextCode']);
Route::get('users/next-code', [\App\Http\Controllers\Api\Permissions\UserController::class, 'nextCode']);
Route::get('employee-contracts/next-code', [\App\Http\Controllers\Api\HR\EmployeeContractController::class, 'nextCode']);
Route::get('employee-contract-amendments/next-code', [\App\Http\Controllers\Api\HR\EmployeeContractAmendmentController::class, 'nextCode']);
Route::get('employee-loans/next-code', [\App\Http\Controllers\Api\HR\EmployeeLoanController::class, 'nextCode']);
Route::get('employee-advances/next-code', [\App\Http\Controllers\Api\HR\EmployeeAdvanceController::class, 'nextCode']);
Route::get('departments/next-code', [\App\Http\Controllers\Api\HR\DepartmentController::class, 'nextCode']);
Route::get('position-levels/next-code', [\App\Http\Controllers\Api\HR\PositionLevelController::class, 'nextCode']);
Route::get('job-positions/next-code', [\App\Http\Controllers\Api\HR\JobPositionController::class, 'nextCode']);
Route::get('job-families/next-code', [\App\Http\Controllers\Api\HR\JobFamilyController::class, 'nextCode']);
Route::get('job-titles/next-code', [\App\Http\Controllers\Api\HR\JobTitleController::class, 'nextCode']);
Route::get('job-grades/next-code', [\App\Http\Controllers\Api\HR\JobGradeController::class, 'nextCode']);
Route::get('salary-scales/next-code', [\App\Http\Controllers\Api\HR\SalaryScaleController::class, 'nextCode']);
Route::get('sales-territories/next-code', [\App\Http\Controllers\Api\Sales\SalesTerritoryController::class, 'nextCode']);
Route::get('sales-territory-types/next-code', function () { return response()->json(['code' => 'STT-00001']); });
Route::get('organization-units/next-code', [\App\Http\Controllers\Api\HR\OrganizationUnitController::class, 'nextCode']);
Route::get('cost-centers/next-code', [\App\Http\Controllers\Api\Accounting\CostCenterController::class, 'nextCode']);
Route::get('treasuries/next-code', [\App\Http\Controllers\Api\Treasury\TreasuryController::class, 'nextCode']);
Route::get('expense-types/next-code', [\App\Http\Controllers\Api\Treasury\ExpenseTypeController::class, 'nextCode']);
Route::get('expenses/next-code', [\App\Http\Controllers\Api\Treasury\ExpenseController::class, 'nextCode']);
Route::get('stock-adjustments/next-code', [\App\Http\Controllers\Api\Inventory\StockAdjustmentController::class, 'nextCode']);
Route::get('stock-counts/next-code', [\App\Http\Controllers\Api\Inventory\StockCountController::class, 'nextCode']);
Route::get('warehouse-transfers/next-code', [\App\Http\Controllers\Api\Inventory\WarehouseTransferController::class, 'nextCode']);
Route::get('inventory-transactions/next-code', [\App\Http\Controllers\Api\Inventory\InventoryTransactionController::class, 'nextCode']);
Route::get('inventory-revaluations/next-code', [\App\Http\Controllers\Api\Inventory\InventoryRevaluationController::class, 'nextCode']);
Route::get('journal-entries/next-code', [\App\Http\Controllers\Api\Accounting\JournalEntryController::class, 'nextCode']);
Route::get('manual-journal-entries/next-code', [\App\Http\Controllers\Api\Accounting\ManualJournalEntryController::class, 'nextCode']);
Route::get('receipt-vouchers/next-code', [\App\Http\Controllers\Api\Treasury\ReceiptVoucherController::class, 'nextCode']);
Route::get('payment-vouchers/next-code', [\App\Http\Controllers\Api\Treasury\PaymentVoucherController::class, 'nextCode']);
Route::get('suppliers/{id}/statement', [\App\Http\Controllers\Api\Suppliers\SupplierController::class, 'statement']);
Route::get('suppliers/{id}/unpaid-invoices', function ($id) {
    $invoices = \App\Models\PurchaseInvoice::where('supplier_id', $id)
        ->where('status', '!=', 'cancelled')
        ->whereRaw('net_total - paid_amount > 0')
        ->orderByDesc('invoice_date')
        ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'remaining_amount']);
    return response()->json($invoices);
});
Route::get('bank-transfers/next-code', [\App\Http\Controllers\Api\Treasury\BankTransferController::class, 'nextCode']);
Route::get('items/next-code', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'nextCode']);
Route::get('item-categories/next-code', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'nextCode']);
Route::get('item-sub-categories/next-code', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'nextCode']);
Route::get('product-companies/next-code', [\App\Http\Controllers\Api\Inventory\ProductCompanyController::class, 'nextCode']);
Route::get('accounts/next-code', [\App\Http\Controllers\Api\Accounting\AccountController::class, 'nextCode']);
Route::get('sales-routes/next-code', [\App\Http\Controllers\Api\Sales\SalesRouteController::class, 'nextCode']);
Route::get('dashboard', [\App\Http\Controllers\Api\Reports\DashboardController::class, 'index'])->middleware('auth:sanctum')->name('dashboard.index');
Route::get('reports/sales', [\App\Http\Controllers\Api\Reports\ReportController::class, 'sales'])->middleware('auth:sanctum')->name('reports.sales');
Route::get('reports/purchases', [\App\Http\Controllers\Api\Reports\ReportController::class, 'purchases'])->middleware('auth:sanctum')->name('reports.purchases');
Route::get('reports/inventory', [\App\Http\Controllers\Api\Reports\ReportController::class, 'inventory'])->middleware('auth:sanctum')->name('reports.inventory');
Route::get('reports/profit', [\App\Http\Controllers\Api\Reports\ReportController::class, 'profit'])->name('reports.profit');
Route::get('reports/warehouse-daily-movement', [\App\Http\Controllers\Api\Reports\ReportController::class, 'warehouseDailyMovement'])->middleware('auth:sanctum')->name('reports.warehouse-daily-movement');
Route::get('reports/customer-daily-sales', [\App\Http\Controllers\Api\Reports\ReportController::class, 'customerDailySales'])->middleware('auth:sanctum')->name('reports.customer-daily-sales');
Route::get('reports/rep-daily-sales', [\App\Http\Controllers\Api\Reports\ReportController::class, 'repDailySales'])->middleware('auth:sanctum')->name('reports.rep-daily-sales');
Route::get('reports/customer-sales', [\App\Http\Controllers\Api\Reports\ReportController::class, 'customerSales'])->middleware('auth:sanctum')->name('reports.customer-sales');
Route::get('reports/rep-movement-by-item', [\App\Http\Controllers\Api\Reports\ReportController::class, 'repMovementByItem'])->middleware('auth:sanctum')->name('reports.rep-movement-by-item');

// ===== شاشة وحدات الأصناف وقوائم أسعارها (للقراءة فقط) =====
Route::get('catalog/items-pricing', [\App\Http\Controllers\Api\CatalogController::class, 'itemsWithPricing']);

// ===== الإقفال اليومي (مراجعة + قفل/فتح) =====
Route::get('closings/status', [\App\Http\Controllers\Api\ClosingController::class, 'status']);
Route::post('closings/close', [\App\Http\Controllers\Api\ClosingController::class, 'close']);
Route::post('closings/reopen', [\App\Http\Controllers\Api\ClosingController::class, 'reopen']);
Route::get('closings/review/inventory', [\App\Http\Controllers\Api\ClosingController::class, 'reviewInventory']);
Route::get('closings/review/finance', [\App\Http\Controllers\Api\ClosingController::class, 'reviewFinance']);

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
        return response()->json(['message' => 'Ø§Ù„ØµÙ†Ù ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);
    }

    // Determine base unit name â€” use default unit (is_default=1) first, then fallback to conv=1
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
            : 'ÙˆØ­Ø¯Ø©';
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

            $txnTypeName = $txn->transactionType?->name ?? '';
            $referenceShort = $txn->reference_type ? class_basename($txn->reference_type) : '';
            $isReturnType = false;
            if ($referenceShort !== '' && stripos($referenceShort, 'return') !== false) {
                $isReturnType = true;
            }
            if (!$isReturnType && $txnTypeName !== '' && (stripos($txnTypeName, 'return') !== false || str_contains($txnTypeName, 'Ù…Ø±ØªØ¬Ø¹'))) {
                $isReturnType = true;
            }

            $qtyIn = 0;
            $qtyOut = 0;
            $qtyLoad = 0;
            $qtyReturn = 0;

            if ($isReturnType) {
                $qtyReturn = abs($qty);
            } else {
                $qtyIn = $isAddition ? abs($qty) : 0;
                $qtyOut = ($isSubtraction && !$isIssueOrder) ? abs($qty) : 0;
                $qtyLoad = ($isSubtraction && $isIssueOrder) ? abs($qty) : 0;
            }

            $runningBalance += $qty;

            $referenceType = null;
            $referenceNo = null;
            $relatedParty = null;

            if ($txn->reference_type) {
                $refShort = class_basename($txn->reference_type);
                $referenceType = match($refShort) {
                    'PurchaseInvoice' => 'ÙØ§ØªÙˆØ±Ø© Ø´Ø±Ø§Ø¡',
                    'SalesInvoice' => 'ÙØ§ØªÙˆØ±Ø© Ø¨ÙŠØ¹',
                    'PurchaseReturn' => 'Ù…Ø±ØªØ¬Ø¹ Ø´Ø±Ø§Ø¡',
                    'SalesReturn' => 'Ù…Ø±ØªØ¬Ø¹ Ø¨ÙŠØ¹',
                    'StockAdjustment' => 'ØªØ¹Ø¯ÙŠÙ„ Ù…Ø®Ø²ÙˆÙ†',
                    'WarehouseTransfer' => 'Ù†Ù‚Ù„ Ù…Ø®Ø²ÙˆÙ†',
                    'InventoryOpeningBalance' => 'Ø±ØµÙŠØ¯ Ø§ÙØªØªØ§Ø­ÙŠ',
                    'IssueOrder' => 'Ø¥Ø°Ù† ØµØ±Ù',
                    'LoadRequest' => 'Ø·Ù„Ø¨ ØªØ­Ù…ÙŠÙ„',
                    'CustomerReturn' => 'Ù…Ø±ØªØ¬Ø¹ Ø¹Ù…ÙŠÙ„',
                    'ReturnOrder' => 'Ø·Ù„Ø¨ Ø¥Ø±Ø¬Ø§Ø¹',
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
    if (!$item) return response()->json(['message' => 'Ø§Ù„ØµÙ†Ù ØºÙŠØ± Ù…ÙˆØ¬ÙˆØ¯'], 404);

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
        'purchase' => 'Ø´Ø±Ø§Ø¡', 'purchase_return' => 'Ù…Ø±ØªØ¬Ø¹ Ø´Ø±Ø§Ø¡',
        'load' => 'ØªØ­Ù…ÙŠÙ„', 'sale' => 'Ø¨ÙŠØ¹',
        'return' => 'Ù…Ø±ØªØ¬Ø¹', 'unload' => 'ØªÙØ±ÙŠØº',
        'transfer_rep' => 'ØªØ­ÙˆÙŠÙ„ Ù…Ù†Ø¯ÙˆØ¨', 'transfer_wh' => 'ØªØ­ÙˆÙŠÙ„ Ù…Ø®Ø²Ù†ÙŠ',
    ];

    $locationNameCache = [];
    $resolveLocation = function($type, $id) use (&$locationNameCache) {
        $key = "$type:$id";
        if (!isset($locationNameCache[$key])) {
            $locationNameCache[$key] = match($type) {
                'warehouse' => \App\Models\Warehouse::find($id)?->name ?? "Ù…Ø®Ø²Ù† #$id",
                'rep' => \App\Models\Employee::find($id)?->full_name_ar ?? "Ù…Ù†Ø¯ÙˆØ¨ #$id",
                'customer' => \App\Models\Customer::find($id)?->name ?? "Ø¹Ù…ÙŠÙ„ #$id",
                'supplier' => \App\Models\Supplier::find($id)?->name ?? "Ù…ÙˆØ±Ø¯ #$id",
                'vehicle' => "Ù…Ø±ÙƒØ¨Ø© #$id",
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
                ? (\App\Models\Warehouse::find($row->warehouse_id)?->name ?? "Ù…Ø®Ø²Ù† #{$row->warehouse_id}")
                : 'â€”');

        $toName = $row->to_location_type && $row->to_location_id
            ? $resolveLocation($row->to_location_type, $row->to_location_id)
            : 'â€”';

        $movements[] = [
            'id' => $row->id,
            'transaction_date' => $row->transaction_date,
            'transaction_no' => $row->transaction_no,
            'ref_number' => $resolveRefNumber($row),
            'movement_type' => $type,
            'type_label' => $typeLabelMap[$type] ?? $row->txn_type_name ?? 'Ø£Ø®Ø±Ù‰',
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
            $data['rep_name'] = $emps->get($id)?->full_name_ar ?? "Ù…Ù†Ø¯ÙˆØ¨ #$id";
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
            'unit' => $item->baseUnit?->name_ar ?? 'ÙˆØ­Ø¯Ø©',
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
require __DIR__.'/api/new_handheld.php';

// ===== Permissions & Roles Custom Routes =====
Route::get('permissions/matrix', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'matrix']);
Route::get('permissions/check/{permission}', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'check']);
Route::post('permissions/check-batch', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'checkBatch']);
Route::post('roles/{role}/permissions', [\App\Http\Controllers\Api\Permissions\RoleController::class, 'updatePermissions']);
Route::post('roles/copy-permissions', [\App\Http\Controllers\Api\Permissions\RoleController::class, 'copyPermissions']);

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
    'roles' => \App\Http\Controllers\Api\Permissions\RoleController::class,
    'permissions' => \App\Http\Controllers\Api\Permissions\PermissionController::class,
    'warehouse-types' => \App\Http\Controllers\Api\Inventory\WarehouseTypeController::class,
    'treasury-types' => \App\Http\Controllers\Api\Treasury\TreasuryTypeController::class,
    'organizational-levels' => \App\Http\Controllers\Api\HR\OrganizationalLevelController::class,
    'organization-unit-types' => \App\Http\Controllers\Api\HR\OrganizationUnitTypeController::class,
    'organization-units' => \App\Http\Controllers\Api\HR\OrganizationUnitController::class,
    'cost-center-types' => \App\Http\Controllers\Api\Accounting\CostCenterTypeController::class,
    'cost-centers' => \App\Http\Controllers\Api\Accounting\CostCenterController::class,
    'sales-territory-types' => \App\Http\Controllers\Api\Sales\SalesTerritoryTypeController::class,
    'sales-territories' => \App\Http\Controllers\Api\Sales\SalesTerritoryController::class,
    'departments' => \App\Http\Controllers\Api\HR\DepartmentController::class,
    'position-levels' => \App\Http\Controllers\Api\HR\PositionLevelController::class,
    'job-positions' => \App\Http\Controllers\Api\HR\JobPositionController::class,
    'job-families' => \App\Http\Controllers\Api\HR\JobFamilyController::class,
    'job-titles' => \App\Http\Controllers\Api\HR\JobTitleController::class,
    'job-grades' => \App\Http\Controllers\Api\HR\JobGradeController::class,
    'salary-scales' => \App\Http\Controllers\Api\HR\SalaryScaleController::class,
    'employee-statuses' => \App\Http\Controllers\Api\HR\EmployeeStatusController::class,
    'employees' => \App\Http\Controllers\Api\HR\EmployeeController::class,
    'employee-assignments' => \App\Http\Controllers\Api\HR\EmployeeAssignmentController::class,
    'contract-types' => \App\Http\Controllers\Api\Pricing\ContractTypeController::class,
    'contract-statuses' => \App\Http\Controllers\Api\Pricing\ContractStatusController::class,
    'employee-contracts' => \App\Http\Controllers\Api\HR\EmployeeContractController::class,
    'employee-contract-amendments' => \App\Http\Controllers\Api\HR\EmployeeContractAmendmentController::class,
    'leave-types' => \App\Http\Controllers\Api\HR\LeaveTypeController::class,
    'leave-requests' => \App\Http\Controllers\Api\HR\LeaveRequestController::class,
    'employee-loans' => \App\Http\Controllers\Api\HR\EmployeeLoanController::class,
    'employee-advances' => \App\Http\Controllers\Api\HR\EmployeeAdvanceController::class,
    'employee-penalties' => \App\Http\Controllers\Api\HR\EmployeePenaltyController::class,
    'employee-rewards' => \App\Http\Controllers\Api\HR\EmployeeRewardController::class,
    'shift-types' => \App\Http\Controllers\Api\HR\ShiftTypeController::class,
    'shifts' => \App\Http\Controllers\Api\HR\ShiftController::class,
    'employee-shifts' => \App\Http\Controllers\Api\HR\EmployeeShiftController::class,
    'attendance-statuses' => \App\Http\Controllers\Api\HR\AttendanceStatusController::class,
    'attendance-records' => \App\Http\Controllers\Api\HR\AttendanceRecordController::class,
    'attendance-adjustments' => \App\Http\Controllers\Api\HR\AttendanceAdjustmentController::class,
    'holidays' => \App\Http\Controllers\Api\HR\HolidayController::class,
    'employee-missions' => \App\Http\Controllers\Api\HR\EmployeeMissionController::class,
    'salary-component-types' => \App\Http\Controllers\Api\HR\SalaryComponentTypeController::class,
    'salary-components' => \App\Http\Controllers\Api\HR\SalaryComponentController::class,
    'employee-salary-structures' => \App\Http\Controllers\Api\HR\EmployeeSalaryStructureController::class,
    'payroll-periods' => \App\Http\Controllers\Api\HR\PayrollPeriodController::class,
    'payroll-runs' => \App\Http\Controllers\Api\HR\PayrollRunController::class,
    'payroll-run-details' => \App\Http\Controllers\Api\HR\PayrollRunDetailController::class,
    'customer-groups' => \App\Http\Controllers\Api\CRM\CustomerGroupController::class,
    'customer-classes' => \App\Http\Controllers\Api\CRM\CustomerClassController::class,
    'customer-types' => \App\Http\Controllers\Api\CRM\CustomerTypeController::class,
    'customer-account-types' => \App\Http\Controllers\Api\CRM\CustomerAccountTypeController::class,
    'trade-program-types' => \App\Http\Controllers\Api\Sales\TradeProgramTypeController::class,
    'customer-addresses' => \App\Http\Controllers\Api\CRM\CustomerAddressController::class,
    'customer-contacts' => \App\Http\Controllers\Api\CRM\CustomerContactController::class,
    'customer-credit-limits' => \App\Http\Controllers\Api\CRM\CustomerCreditLimitController::class,
    'item-categories' => \App\Http\Controllers\Api\Inventory\ItemCategoryController::class,
    'item-sub-categories' => \App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class,
    'item-units' => \App\Http\Controllers\Api\Inventory\ItemUnitController::class,
    'item-prices' => \App\Http\Controllers\Api\Inventory\ItemPriceController::class,
    'item-barcodes' => \App\Http\Controllers\Api\Inventory\ItemBarcodeController::class,
    'item-batches' => \App\Http\Controllers\Api\Inventory\ItemBatchController::class,
    'items' => \App\Http\Controllers\Api\Inventory\ItemController::class,
    'product-companies' => \App\Http\Controllers\Api\Inventory\ProductCompanyController::class,
    'price-levels' => \App\Http\Controllers\Api\Pricing\PriceLevelController::class,
    'customer-price-levels' => \App\Http\Controllers\Api\Pricing\CustomerPriceLevelController::class,
    'customer-special-prices' => \App\Http\Controllers\Api\Pricing\CustomerSpecialPriceController::class,
    'pricing-methods' => \App\Http\Controllers\Api\Pricing\PricingMethodController::class,
    'pricing-rules' => \App\Http\Controllers\Api\Pricing\PricingRuleController::class,
    'pricing-rule-conditions' => \App\Http\Controllers\Api\Pricing\PricingRuleConditionController::class,
    'pricing-rule-items' => \App\Http\Controllers\Api\Pricing\PricingRuleItemController::class,
    'quantity-price-breaks' => \App\Http\Controllers\Api\Pricing\QuantityPriceBreakController::class,
    'contract-prices' => \App\Http\Controllers\Api\Pricing\ContractPriceController::class,
    'pricing-calculations' => \App\Http\Controllers\Api\Pricing\PricingCalculationController::class,
    'pricing-calculation-details' => \App\Http\Controllers\Api\Pricing\PricingCalculationDetailController::class,
    'price-approval-requests' => \App\Http\Controllers\Api\Pricing\PriceApprovalRequestController::class,
    'price-approval-steps' => \App\Http\Controllers\Api\Pricing\PriceApprovalStepController::class,
    'pricing-exceptions' => \App\Http\Controllers\Api\Pricing\PricingExceptionController::class,
    'pricing-audit-log' => \App\Http\Controllers\Api\Pricing\PricingAuditLogController::class,
    'customer-price-lists' => \App\Http\Controllers\Api\Pricing\CustomerPriceListController::class,
    'inventory-transaction-types' => \App\Http\Controllers\Api\Inventory\InventoryTransactionTypeController::class,
    'inventory-transactions' => \App\Http\Controllers\Api\Inventory\InventoryTransactionController::class,
    'inventory-transaction-items' => \App\Http\Controllers\Api\Inventory\InventoryTransactionItemController::class,
    'inventory-opening-balances' => \App\Http\Controllers\Api\Inventory\InventoryOpeningBalanceController::class,
    'stock-adjustments' => \App\Http\Controllers\Api\Inventory\StockAdjustmentController::class,
    'stock-adjustment-items' => \App\Http\Controllers\Api\Inventory\StockAdjustmentItemController::class,
    'stock-counts' => \App\Http\Controllers\Api\Inventory\StockCountController::class,
    'stock-count-items' => \App\Http\Controllers\Api\Inventory\StockCountItemController::class,
    'warehouse-transfers' => \App\Http\Controllers\Api\Inventory\WarehouseTransferController::class,
    'warehouse-transfer-items' => \App\Http\Controllers\Api\Inventory\WarehouseTransferItemController::class,
    'inventory-revaluations' => \App\Http\Controllers\Api\Inventory\InventoryRevaluationController::class,
    'inventory-revaluation-items' => \App\Http\Controllers\Api\Inventory\InventoryRevaluationItemController::class,
    'load-requests' => \App\Http\Controllers\Api\Fleet\LoadRequestController::class,
    'load-request-items' => \App\Http\Controllers\Api\Fleet\LoadRequestItemController::class,
    'issue-orders' => \App\Http\Controllers\Api\Inventory\IssueOrderController::class,
    'issue-order-items' => \App\Http\Controllers\Api\Inventory\IssueOrderItemController::class,
    'return-orders' => \App\Http\Controllers\Api\Sales\ReturnOrderController::class,
    'return-order-items' => \App\Http\Controllers\Api\Sales\ReturnOrderItemController::class,
    'distribution-plans' => \App\Http\Controllers\Api\Sales\DistributionPlanController::class,
    'salesman-assignments' => \App\Http\Controllers\Api\Sales\SalesmanAssignmentController::class,
    'sales-routes' => \App\Http\Controllers\Api\Sales\SalesRouteController::class,
    'route-schedules' => \App\Http\Controllers\Api\Sales\RouteScheduleController::class,
    'route-customers' => \App\Http\Controllers\Api\Sales\RouteCustomerController::class,
    'customer-visits' => \App\Http\Controllers\Api\CRM\CustomerVisitController::class,
    'route-visits' => \App\Http\Controllers\Api\Sales\RouteVisitController::class,
    'sales-incentives' => \App\Http\Controllers\Api\Sales\SalesIncentiveController::class,
    'sales-incentive-conditions' => \App\Http\Controllers\Api\Sales\SalesIncentiveConditionController::class,
    'sales-incentive-condition-items' => \App\Http\Controllers\Api\Sales\SalesIncentiveConditionItemController::class,
    'sales-incentive-rewards' => \App\Http\Controllers\Api\Sales\SalesIncentiveRewardController::class,
    'sales-invoices' => \App\Http\Controllers\Api\Sales\SalesInvoiceController::class,
    'sales-invoice-items' => \App\Http\Controllers\Api\Sales\SalesInvoiceItemController::class,
    'sales-invoice-discounts' => \App\Http\Controllers\Api\Sales\SalesInvoiceDiscountController::class,
    'sales-invoice-taxes' => \App\Http\Controllers\Api\Sales\SalesInvoiceTaxController::class,
    'sales-invoice-incentives' => \App\Http\Controllers\Api\Sales\SalesInvoiceIncentiveController::class,
    'collections' => \App\Http\Controllers\Api\Sales\CollectionController::class,
    'salesman-settlements' => \App\Http\Controllers\Api\Sales\SalesmanSettlementController::class,
    'salesman-debts' => \App\Http\Controllers\Api\Sales\SalesmanDebtController::class,
    'customer-debts' => \App\Http\Controllers\Api\Sales\SalesmanDebtController::class,
    'rep-debt-payments' => \App\Http\Controllers\Api\Sales\SalesmanDebtController::class,
    'customer-returns' => \App\Http\Controllers\Api\Sales\CustomerReturnController::class,
    'customer-return-items' => \App\Http\Controllers\Api\Sales\CustomerReturnItemController::class,
    'daily-distribution-dashboards' => \App\Http\Controllers\Api\Sales\DailyDistributionDashboardController::class,
    'treasuries' => \App\Http\Controllers\Api\Treasury\TreasuryController::class,
    'treasury-shifts' => \App\Http\Controllers\Api\Treasury\TreasuryShiftController::class,
    'treasury-shift-transactions' => \App\Http\Controllers\Api\Treasury\TreasuryShiftTransactionController::class,
    'treasury-counts' => \App\Http\Controllers\Api\Treasury\TreasuryCountController::class,
    'treasury-count-details' => \App\Http\Controllers\Api\Treasury\TreasuryCountDetailController::class,
    'treasury-transfers' => \App\Http\Controllers\Api\Treasury\TreasuryTransferController::class,
    'treasury-adjustments' => \App\Http\Controllers\Api\Treasury\TreasuryAdjustmentController::class,
    'treasury-opening-balances' => \App\Http\Controllers\Api\Treasury\TreasuryOpeningBalanceController::class,
    'bank-opening-balances' => \App\Http\Controllers\Api\Treasury\BankOpeningBalanceController::class,
    'treasury-bank-transfers' => \App\Http\Controllers\Api\Treasury\TreasuryBankTransferController::class,
    'bank-supplier-payments' => \App\Http\Controllers\Api\Treasury\BankSupplierPaymentController::class,
    'treasury-daily-closings' => \App\Http\Controllers\Api\Treasury\TreasuryDailyClosingController::class,
    'treasury-closing-details' => \App\Http\Controllers\Api\Treasury\TreasuryClosingDetailController::class,
    'treasury-custodies' => \App\Http\Controllers\Api\Treasury\TreasuryCustodyController::class,
    'treasury-custody-transactions' => \App\Http\Controllers\Api\Treasury\TreasuryCustodyTransactionController::class,
    'treasury-cash-limits' => \App\Http\Controllers\Api\Treasury\TreasuryCashLimitController::class,
    'treasury-alerts' => \App\Http\Controllers\Api\Treasury\TreasuryAlertController::class,
    'treasury-transactions' => \App\Http\Controllers\Api\Treasury\TreasuryTransactionController::class,
    'expense-types' => \App\Http\Controllers\Api\Treasury\ExpenseTypeController::class,
    'expenses' => \App\Http\Controllers\Api\Treasury\ExpenseController::class,
    'account-types' => \App\Http\Controllers\Api\Accounting\AccountTypeController::class,
    'account-groups' => \App\Http\Controllers\Api\Accounting\AccountGroupController::class,
    'journal-entry-types' => \App\Http\Controllers\Api\Accounting\JournalEntryTypeController::class,
    'journal-entries' => \App\Http\Controllers\Api\Accounting\JournalEntryController::class,
    'journal-entry-lines' => \App\Http\Controllers\Api\Accounting\JournalEntryLineController::class,
    'fiscal-years' => \App\Http\Controllers\Api\Accounting\FiscalYearController::class,
    'accounting-periods' => \App\Http\Controllers\Api\Accounting\AccountingPeriodController::class,
    'opening-balances' => \App\Http\Controllers\Api\Accounting\OpeningBalanceController::class,
    'opening-balance-documents' => \App\Http\Controllers\Api\Accounting\OpeningBalanceDocumentController::class,
    'manual-journal-entries' => \App\Http\Controllers\Api\Accounting\ManualJournalEntryController::class,
    'manual-journal-entry-lines' => \App\Http\Controllers\Api\Accounting\ManualJournalEntryLineController::class,
    'bank-accounts' => \App\Http\Controllers\Api\Treasury\BankAccountController::class,
    'bank-transfers' => \App\Http\Controllers\Api\Treasury\BankTransferController::class,
    'bank-reconciliations' => \App\Http\Controllers\Api\Treasury\BankReconciliationController::class,
    'receipt-vouchers' => \App\Http\Controllers\Api\Treasury\ReceiptVoucherController::class,
    'payment-vouchers' => \App\Http\Controllers\Api\Treasury\PaymentVoucherController::class,
    'customer-ledger' => \App\Http\Controllers\Api\CRM\CustomerLedgerController::class,
    'supplier-ledger' => \App\Http\Controllers\Api\Suppliers\SupplierLedgerController::class,
    'tax-types' => \App\Http\Controllers\Api\Tax\TaxTypeController::class,
    'tax-rates' => \App\Http\Controllers\Api\Tax\TaxRateController::class,
    'tax-groups' => \App\Http\Controllers\Api\Tax\TaxGroupController::class,
    'tax-group-details' => \App\Http\Controllers\Api\Tax\TaxGroupDetailController::class,
    'tax-exemptions' => \App\Http\Controllers\Api\Tax\TaxExemptionController::class,
    'customer-tax-profiles' => \App\Http\Controllers\Api\CRM\CustomerTaxProfileController::class,
    'supplier-tax-profiles' => \App\Http\Controllers\Api\Suppliers\SupplierTaxProfileController::class,
    'item-tax-profiles' => \App\Http\Controllers\Api\Tax\ItemTaxProfileController::class,
    'tax-rules' => \App\Http\Controllers\Api\Tax\TaxRuleController::class,
    'tax-calculations' => \App\Http\Controllers\Api\Tax\TaxCalculationController::class,
    'tax-calculation-details' => \App\Http\Controllers\Api\Tax\TaxCalculationDetailController::class,
    'tax-jurisdictions' => \App\Http\Controllers\Api\Tax\TaxJurisdictionController::class,
    'tax-periods' => \App\Http\Controllers\Api\Tax\TaxPeriodController::class,
    'tax-returns' => \App\Http\Controllers\Api\Tax\TaxReturnController::class,
    'tax-return-details' => \App\Http\Controllers\Api\Tax\TaxReturnDetailController::class,
    'withholding-tax-certificates' => \App\Http\Controllers\Api\Tax\WithholdingTaxCertificateController::class,
    'purchase-requests' => \App\Http\Controllers\Api\Purchase\PurchaseRequestController::class,
    'purchase-request-items' => \App\Http\Controllers\Api\Purchase\PurchaseRequestItemController::class,
    'supplier-groups' => \App\Http\Controllers\Api\Suppliers\SupplierGroupController::class,
    'supplier-contacts' => \App\Http\Controllers\Api\Suppliers\SupplierContactController::class,
    'supplier-quotations' => \App\Http\Controllers\Api\Purchase\SupplierQuotationController::class,
    'supplier-quotation-items' => \App\Http\Controllers\Api\Purchase\SupplierQuotationItemController::class,
    'purchase-orders' => \App\Http\Controllers\Api\Purchase\PurchaseOrderController::class,
    'purchase-order-items' => \App\Http\Controllers\Api\Purchase\PurchaseOrderItemController::class,
    'purchase-receipts' => \App\Http\Controllers\Api\Purchase\PurchaseReceiptController::class,
    'purchase-receipt-items' => \App\Http\Controllers\Api\Purchase\PurchaseReceiptItemController::class,
    'purchase-invoices' => \App\Http\Controllers\Api\Purchase\PurchaseInvoiceController::class,
    'purchase-invoice-items' => \App\Http\Controllers\Api\Purchase\PurchaseInvoiceItemController::class,
    'purchase-returns' => \App\Http\Controllers\Api\Purchase\PurchaseReturnController::class,
    'purchase-return-items' => \App\Http\Controllers\Api\Purchase\PurchaseReturnItemController::class,
    'purchase-expenses' => \App\Http\Controllers\Api\Purchase\PurchaseExpenseController::class,
    'driver' => \App\Http\Controllers\Api\Fleet\DriverController::class,
    'drivers' => \App\Http\Controllers\Api\Fleet\DriverController::class,
    'vehicle-assignments' => \App\Http\Controllers\Api\Fleet\VehicleAssignmentController::class,
    'vehicle-fuel-transactions' => \App\Http\Controllers\Api\Fleet\VehicleFuelTransactionController::class,
    'vehicle-maintenance' => \App\Http\Controllers\Api\Fleet\VehicleMaintenanceController::class,
    'vehicle-expenses' => \App\Http\Controllers\Api\Fleet\VehicleExpenseController::class,
    'vehicle-loadings' => \App\Http\Controllers\Api\Fleet\VehicleLoadingController::class,
    'vehicle-warehouses' => \App\Http\Controllers\Api\Fleet\VehicleWarehouseController::class,
    'vehicle-inventory-transactions' => \App\Http\Controllers\Api\Fleet\VehicleInventoryTransactionController::class,
    'vehicle-stock-balances' => \App\Http\Controllers\Api\Fleet\VehicleStockBalanceController::class,
    'vehicle-loads' => \App\Http\Controllers\Api\Fleet\VehicleLoadController::class,
    'vehicle-load-items' => \App\Http\Controllers\Api\Fleet\VehicleLoadItemController::class,
    'vehicle-unloads' => \App\Http\Controllers\Api\Fleet\VehicleUnloadController::class,
    'vehicle-unload-items' => \App\Http\Controllers\Api\Fleet\VehicleUnloadItemController::class,
    'vehicle-cash-accounts' => \App\Http\Controllers\Api\Fleet\VehicleCashAccountController::class,
    'vehicle-cash-transactions' => \App\Http\Controllers\Api\Fleet\VehicleCashTransactionController::class,
    'vehicle-daily-expenses' => \App\Http\Controllers\Api\Fleet\VehicleDailyExpenseController::class,
    'vehicle-daily-shifts' => \App\Http\Controllers\Api\Fleet\VehicleDailyShiftController::class,
    'vehicle-stock-counts' => \App\Http\Controllers\Api\Fleet\VehicleStockCountController::class,
    'vehicle-stock-count-items' => \App\Http\Controllers\Api\Fleet\VehicleStockCountItemController::class,
    'vehicle-settlements' => \App\Http\Controllers\Api\Fleet\VehicleSettlementController::class,
    'vehicle-settlement-items' => \App\Http\Controllers\Api\Fleet\VehicleSettlementItemController::class,
    'vehicle-deposits' => \App\Http\Controllers\Api\Fleet\VehicleDepositController::class,
    'vehicle-documents' => \App\Http\Controllers\Api\Fleet\VehicleDocumentController::class,
    'vehicle-ownership-history' => \App\Http\Controllers\Api\Fleet\VehicleOwnershipHistoryController::class,
    'vehicle-meter-readings' => \App\Http\Controllers\Api\Fleet\VehicleMeterReadingController::class,
    'vehicle-maintenance-plans' => \App\Http\Controllers\Api\Fleet\VehicleMaintenancePlanController::class,
    'vehicle-work-orders' => \App\Http\Controllers\Api\Fleet\VehicleWorkOrderController::class,
    'vehicle-work-order-items' => \App\Http\Controllers\Api\Fleet\VehicleWorkOrderItemController::class,
    'vehicle-maintenance-parts' => \App\Http\Controllers\Api\Fleet\VehicleMaintenancePartController::class,
    'vehicle-tires' => \App\Http\Controllers\Api\Fleet\VehicleTireController::class,
    'vehicle-tire-movements' => \App\Http\Controllers\Api\Fleet\VehicleTireMovementController::class,
    'vehicle-tire-inspections' => \App\Http\Controllers\Api\Fleet\VehicleTireInspectionController::class,
    'vehicle-batteries' => \App\Http\Controllers\Api\Fleet\VehicleBatteryController::class,
    'vehicle-fuel-cards' => \App\Http\Controllers\Api\Fleet\VehicleFuelCardController::class,
    'vehicle-fuel-stations' => \App\Http\Controllers\Api\Fleet\VehicleFuelStationController::class,
    'vehicle-fuel-prices' => \App\Http\Controllers\Api\Fleet\VehicleFuelPriceController::class,
    'driver-licenses' => \App\Http\Controllers\Api\Fleet\DriverLicenseController::class,
    'driver-training' => \App\Http\Controllers\Api\Fleet\DriverTrainingController::class,
    'driver-violations' => \App\Http\Controllers\Api\Fleet\DriverViolationController::class,
    'driver-medical-tests' => \App\Http\Controllers\Api\Fleet\DriverMedicalTestController::class,
    'driver-behavior-scores' => \App\Http\Controllers\Api\Fleet\DriverBehaviorScoreController::class,
    'vehicle-accidents' => \App\Http\Controllers\Api\Fleet\VehicleAccidentController::class,
    'vehicle-insurance' => \App\Http\Controllers\Api\Fleet\VehicleInsuranceController::class,
    'vehicle-insurance-claims' => \App\Http\Controllers\Api\Fleet\VehicleInsuranceClaimController::class,
    'vehicle-reservations' => \App\Http\Controllers\Api\Fleet\VehicleReservationController::class,
    'geofences' => \App\Http\Controllers\Api\Fleet\GeofenceController::class,
    'vehicle-geofence-events' => \App\Http\Controllers\Api\Fleet\VehicleGeofenceEventController::class,
    'vehicle-speed-violations' => \App\Http\Controllers\Api\Fleet\VehicleSpeedViolationController::class,
    'vehicle-idle-time' => \App\Http\Controllers\Api\Fleet\VehicleIdleTimeController::class,
    'vehicle-trip-history' => \App\Http\Controllers\Api\Fleet\VehicleTripHistoryController::class,
    'vehicle-cost-analysis' => \App\Http\Controllers\Api\Fleet\VehicleCostAnalysisController::class,
    'vehicle-alerts' => \App\Http\Controllers\Api\Fleet\VehicleAlertController::class,
    'asset-categories' => \App\Http\Controllers\Api\Assets\AssetCategoryController::class,
    'assets' => \App\Http\Controllers\Api\Assets\AssetController::class,
    'asset-assignments' => \App\Http\Controllers\Api\Assets\AssetAssignmentController::class,
    'asset-depreciations' => \App\Http\Controllers\Api\Assets\AssetDepreciationController::class,
    'leads' => \App\Http\Controllers\Api\CRM\LeadController::class,
    'lead-activities' => \App\Http\Controllers\Api\CRM\LeadActivityController::class,
    'opportunities' => \App\Http\Controllers\Api\CRM\OpportunityController::class,
    'opportunity-stages' => \App\Http\Controllers\Api\CRM\OpportunityStageController::class,
    'document-categories' => \App\Http\Controllers\Api\Settings\DocumentCategoryController::class,
    'documents' => \App\Http\Controllers\Api\Settings\DocumentController::class,
    'audit-logs' => \App\Http\Controllers\Api\Integration\AuditLogController::class,
    'login-logs' => \App\Http\Controllers\Api\Auth\LoginLogController::class,
    'api-logs' => \App\Http\Controllers\Api\Integration\ApiLogController::class,
    'workflow-definitions' => \App\Http\Controllers\Api\Workflows\WorkflowDefinitionController::class,
    'workflow-steps' => \App\Http\Controllers\Api\Workflows\WorkflowStepController::class,
    'approval-requests' => \App\Http\Controllers\Api\Workflows\ApprovalRequestController::class,
    'approval-actions' => \App\Http\Controllers\Api\Workflows\ApprovalActionController::class,
    'notification-templates' => \App\Http\Controllers\Api\Notifications\NotificationTemplateController::class,
    'notifications' => \App\Http\Controllers\Api\Notifications\NotificationController::class,
    'notification-queue' => \App\Http\Controllers\Api\Notifications\NotificationQueueController::class,
    'kpi-definitions' => \App\Http\Controllers\Api\HR\KpiDefinitionController::class,
    'kpi-targets' => \App\Http\Controllers\Api\HR\KpiTargetController::class,
    'kpi-results' => \App\Http\Controllers\Api\HR\KpiResultController::class,
    'sales-targets' => \App\Http\Controllers\Api\Sales\SalesTargetController::class,
    'sales-target-details' => \App\Http\Controllers\Api\Sales\SalesTargetDetailController::class,
    'budgets' => \App\Http\Controllers\Api\Accounting\BudgetController::class,
    'budget-lines' => \App\Http\Controllers\Api\Accounting\BudgetLineController::class,
    'demand-forecasts' => \App\Http\Controllers\Api\Sales\DemandForecastController::class,
    'forecast-history' => \App\Http\Controllers\Api\Sales\ForecastHistoryController::class,
    'replenishment-rules' => \App\Http\Controllers\Api\Inventory\ReplenishmentRuleController::class,
    'replenishment-suggestions' => \App\Http\Controllers\Api\Inventory\ReplenishmentSuggestionController::class,
    'route-templates' => \App\Http\Controllers\Api\Sales\RouteTemplateController::class,
    'route-stops' => \App\Http\Controllers\Api\Sales\RouteStopController::class,
    'gps-tracking-sessions' => \App\Http\Controllers\Api\Fleet\GpsTrackingSessionController::class,
    'gps-tracking-points' => \App\Http\Controllers\Api\Fleet\GpsTrackingPointController::class,
    'e-invoice-providers' => \App\Http\Controllers\Api\Tax\EInvoiceProviderController::class,
    'e-invoice-transactions' => \App\Http\Controllers\Api\Tax\EInvoiceTransactionController::class,
    'message-templates' => \App\Http\Controllers\Api\Notifications\MessageTemplateController::class,
    'message-logs' => \App\Http\Controllers\Api\Notifications\MessageLogController::class,
    'sync-batches' => \App\Http\Controllers\Api\Integration\SyncBatchController::class,
    'sync-logs' => \App\Http\Controllers\Api\Integration\SyncLogController::class,
    'mobile-devices' => \App\Http\Controllers\Api\Integration\MobileDeviceController::class,
    'master-data-request-types' => \App\Http\Controllers\Api\Workflows\MasterDataTypeController::class,
    'master-data-requests' => \App\Http\Controllers\Api\Workflows\MasterDataRequestController::class,
    'master-data-request-steps' => \App\Http\Controllers\Api\Workflows\MasterDataRequestStepController::class,
    'master-data-request-history' => \App\Http\Controllers\Api\Workflows\MasterDataRequestHistoryController::class,
    'master-data-workflows' => \App\Http\Controllers\Api\Workflows\MasterDataWorkflowController::class,
    'master-data-workflow-steps' => \App\Http\Controllers\Api\Workflows\MasterDataWorkflowStepController::class,
    'customer-agreement-types' => \App\Http\Controllers\Api\CRM\CustomerAgreementTypeController::class,
    'customer-agreements' => \App\Http\Controllers\Api\CRM\CustomerAgreementController::class,
    'customer-agreement-items' => \App\Http\Controllers\Api\CRM\CustomerAgreementItemController::class,
    'marketing-support-types' => \App\Http\Controllers\Api\CRM\MarketingSupportTypeController::class,
    'customer-marketing-supports' => \App\Http\Controllers\Api\CRM\CustomerMarketingSupportController::class,
    'customer-rebate-rules' => \App\Http\Controllers\Api\Pricing\CustomerRebateRuleController::class,
    'customer-agreement-targets' => \App\Http\Controllers\Api\CRM\CustomerAgreementTargetController::class,
    'customer-agreement-payments' => \App\Http\Controllers\Api\CRM\CustomerAgreementPaymentController::class,
    'customer-agreement-history' => \App\Http\Controllers\Api\CRM\CustomerAgreementHistoryController::class,
    'marketing-asset-categories' => \App\Http\Controllers\Api\CRM\MarketingAssetCategoryController::class,
    'marketing-assets' => \App\Http\Controllers\Api\CRM\MarketingAssetController::class,
    'customer-marketing-assets' => \App\Http\Controllers\Api\CRM\CustomerMarketingAssetController::class,
    'marketing-asset-movements' => \App\Http\Controllers\Api\CRM\MarketingAssetMovementController::class,
    'marketing-asset-maintenance' => \App\Http\Controllers\Api\CRM\MarketingAssetMaintenanceController::class,
    'merchandising-visits' => \App\Http\Controllers\Api\Merchandising\MerchandisingVisitController::class,
    'merchandising-checklists' => \App\Http\Controllers\Api\Merchandising\MerchandisingChecklistController::class,
    'merchandising-visit-details' => \App\Http\Controllers\Api\Merchandising\MerchandisingVisitDetailController::class,
    'merchandising-photos' => \App\Http\Controllers\Api\Merchandising\MerchandisingPhotoController::class,
    'marketing-materials' => \App\Http\Controllers\Api\CRM\MarketingMaterialController::class,
    'customer-marketing-materials' => \App\Http\Controllers\Api\CRM\CustomerMarketingMaterialController::class,
    'marketing-campaigns' => \App\Http\Controllers\Api\CRM\MarketingCampaignController::class,
    'marketing-campaign-customers' => \App\Http\Controllers\Api\CRM\MarketingCampaignCustomerController::class,
    'competitors' => \App\Http\Controllers\Api\Surveys\CompetitorController::class,
    'competitor-brands' => \App\Http\Controllers\Api\Surveys\CompetitorBrandController::class,
    'competitor-products' => \App\Http\Controllers\Api\Surveys\CompetitorProductController::class,
    'competitor-price-surveys' => \App\Http\Controllers\Api\Surveys\CompetitorPriceSurveyController::class,
    'competitor-price-survey-items' => \App\Http\Controllers\Api\Surveys\CompetitorPriceSurveyItemController::class,
    'competitor-promotions' => \App\Http\Controllers\Api\Surveys\CompetitorPromotionController::class,
    'competitor-promotion-items' => \App\Http\Controllers\Api\Surveys\CompetitorPromotionItemController::class,
    'shelf-share-surveys' => \App\Http\Controllers\Api\Merchandising\ShelfShareSurveyController::class,
    'shelf-share-items' => \App\Http\Controllers\Api\Merchandising\ShelfShareItemController::class,
    'competitor-new-products' => \App\Http\Controllers\Api\Surveys\CompetitorNewProductController::class,
    'market-issues' => \App\Http\Controllers\Api\Merchandising\MarketIssueController::class,
    'competitor-photos' => \App\Http\Controllers\Api\Surveys\CompetitorPhotoController::class,
    'survey-categories' => \App\Http\Controllers\Api\Surveys\SurveyCategoryController::class,
    'surveys' => \App\Http\Controllers\Api\Surveys\SurveyController::class,
    'survey-questions' => \App\Http\Controllers\Api\Surveys\SurveyQuestionController::class,
    'survey-question-options' => \App\Http\Controllers\Api\Surveys\SurveyQuestionOptionController::class,
    'survey-question-rules' => \App\Http\Controllers\Api\Surveys\SurveyQuestionRuleController::class,
    'survey-responses' => \App\Http\Controllers\Api\Surveys\SurveyResponseController::class,
    'survey-response-answers' => \App\Http\Controllers\Api\Surveys\SurveyResponseAnswerController::class,
    'survey-response-options' => \App\Http\Controllers\Api\Surveys\SurveyResponseOptionController::class,
    'survey-response-photos' => \App\Http\Controllers\Api\Surveys\SurveyResponsePhotoController::class,
    'survey-scoring-rules' => \App\Http\Controllers\Api\Surveys\SurveyScoringRuleController::class,
    'survey-scores' => \App\Http\Controllers\Api\Surveys\SurveyScoreController::class,
    'survey-assignments' => \App\Http\Controllers\Api\Surveys\SurveyAssignmentController::class,
    'merchandising-standards' => \App\Http\Controllers\Api\Merchandising\MerchandisingStandardController::class,
    'merchandising-standard-items' => \App\Http\Controllers\Api\Merchandising\MerchandisingStandardItemController::class,
    'display-locations' => \App\Http\Controllers\Api\Inventory\DisplayLocationController::class,
    'merchandising-audits' => \App\Http\Controllers\Api\Merchandising\MerchandisingAuditController::class,
    'merchandising-audit-details' => \App\Http\Controllers\Api\Merchandising\MerchandisingAuditDetailController::class,
    'shelf-audits' => \App\Http\Controllers\Api\Merchandising\ShelfAuditController::class,
    'shelf-audit-items' => \App\Http\Controllers\Api\Merchandising\ShelfAuditItemController::class,
    'competitor-shelf-items' => \App\Http\Controllers\Api\Surveys\CompetitorShelfItemController::class,
    'availability-audits' => \App\Http\Controllers\Api\Merchandising\AvailabilityAuditController::class,
    'refrigerator-audits' => \App\Http\Controllers\Api\Merchandising\RefrigeratorAuditController::class,
    'posm-audits' => \App\Http\Controllers\Api\Merchandising\PosmAuditController::class,
    'merchandising-audit-photos' => \App\Http\Controllers\Api\Merchandising\MerchandisingAuditPhotoController::class,
    'merchandising-tasks' => \App\Http\Controllers\Api\Merchandising\MerchandisingTaskController::class,
    'merchandising-task-assignments' => \App\Http\Controllers\Api\Merchandising\MerchandisingTaskAssignmentController::class,
    'notification-types' => \App\Http\Controllers\Api\Notifications\NotificationTypeController::class,
    'notification-channels' => \App\Http\Controllers\Api\Notifications\NotificationChannelController::class,
    'notification-events' => \App\Http\Controllers\Api\Notifications\NotificationEventController::class,
    'notification-rules' => \App\Http\Controllers\Api\Notifications\NotificationRuleController::class,
    'notification-rule-recipients' => \App\Http\Controllers\Api\Notifications\NotificationRuleRecipientController::class,
    'notification-recipients' => \App\Http\Controllers\Api\Notifications\NotificationRecipientController::class,
    'notification-deliveries' => \App\Http\Controllers\Api\Notifications\NotificationDeliveryController::class,
    'notification-preferences' => \App\Http\Controllers\Api\Notifications\NotificationPreferenceController::class,
    'notification-groups' => \App\Http\Controllers\Api\Notifications\NotificationGroupController::class,
    'notification-group-members' => \App\Http\Controllers\Api\Notifications\NotificationGroupMemberController::class,
    'alert-rules' => \App\Http\Controllers\Api\Notifications\AlertRuleController::class,
    'alerts' => \App\Http\Controllers\Api\Notifications\AlertController::class,
    'alert-actions' => \App\Http\Controllers\Api\Notifications\AlertActionController::class,
    'scheduled-notifications' => \App\Http\Controllers\Api\Notifications\ScheduledNotificationController::class,
    'integration-providers' => \App\Http\Controllers\Api\Integration\IntegrationProviderController::class,
    'integration-accounts' => \App\Http\Controllers\Api\Integration\IntegrationAccountController::class,
    'integration-endpoints' => \App\Http\Controllers\Api\Integration\IntegrationEndpointController::class,
    'integration-events' => \App\Http\Controllers\Api\Integration\IntegrationEventController::class,
    'integration-event-subscriptions' => \App\Http\Controllers\Api\Integration\IntegrationEventSubscriptionController::class,
    'api-clients' => \App\Http\Controllers\Api\Integration\ApiClientController::class,
    'api-tokens' => \App\Http\Controllers\Api\Integration\ApiTokenController::class,
    'api-permissions' => \App\Http\Controllers\Api\Permissions\ApiPermissionController::class,
    'webhook-endpoints' => \App\Http\Controllers\Api\Integration\WebhookEndpointController::class,
    'webhook-subscriptions' => \App\Http\Controllers\Api\Integration\WebhookSubscriptionController::class,
    'webhook-logs' => \App\Http\Controllers\Api\Integration\WebhookLogController::class,
    'api-request-logs' => \App\Http\Controllers\Api\Integration\ApiRequestLogController::class,
    'api-rate-limits' => \App\Http\Controllers\Api\Integration\ApiRateLimitController::class,
    'integration-jobs' => \App\Http\Controllers\Api\Integration\IntegrationJobController::class,
    'integration-job-runs' => \App\Http\Controllers\Api\Integration\IntegrationJobRunController::class,
    'external-documents' => \App\Http\Controllers\Api\Settings\ExternalDocumentController::class,
    'external-document-logs' => \App\Http\Controllers\Api\Settings\ExternalDocumentLogController::class,
    'integration-error-logs' => \App\Http\Controllers\Api\Integration\IntegrationErrorLogController::class,
    'api-audit-logs' => \App\Http\Controllers\Api\Integration\ApiAuditLogController::class,
    'workflow-types' => \App\Http\Controllers\Api\Workflows\WorkflowTypeController::class,
    'workflows' => \App\Http\Controllers\Api\Workflows\WorkflowController::class,
    'workflow-conditions' => \App\Http\Controllers\Api\Workflows\WorkflowConditionController::class,
    'workflow-instances' => \App\Http\Controllers\Api\Workflows\WorkflowInstanceController::class,
    'workflow-instance-steps' => \App\Http\Controllers\Api\Workflows\WorkflowInstanceStepController::class,
    'workflow-delegations' => \App\Http\Controllers\Api\Workflows\WorkflowDelegationController::class,
    'workflow-escalations' => \App\Http\Controllers\Api\Workflows\WorkflowEscalationController::class,
    'workflow-notifications' => \App\Http\Controllers\Api\Workflows\WorkflowNotificationController::class,
    'workflow-actions-log' => \App\Http\Controllers\Api\Workflows\WorkflowActionLogController::class,
    'workflow-templates' => \App\Http\Controllers\Api\Workflows\WorkflowTemplateController::class,
    'workflow-template-steps' => \App\Http\Controllers\Api\Workflows\WorkflowTemplateStepController::class,
    'workflow-roles' => \App\Http\Controllers\Api\Workflows\WorkflowRoleController::class,
    'workflow-sla-rules' => \App\Http\Controllers\Api\Workflows\WorkflowSlaRuleController::class,
    'vehicle-types' => \App\Http\Controllers\Api\Fleet\VehicleTypeController::class,
    'vehicles' => \App\Http\Controllers\Api\Fleet\VehicleController::class,
];

Route::get('route-schedules/today-count', [\App\Http\Controllers\Api\Sales\RouteScheduleController::class, 'todayCount']);

foreach ($resources as $uri => $controller) {
    if (class_exists($controller)) {
        Route::resource($uri, $controller);
    }
}

Route::post('return-orders/{returnOrder}/approve', [\App\Http\Controllers\Api\Sales\ReturnOrderController::class, 'approve']);
Route::post('return-orders/{returnOrder}/reject', [\App\Http\Controllers\Api\Sales\ReturnOrderController::class, 'reject']);

Route::post('salesman-debts/{salesmanDebt}/collect', [\App\Http\Controllers\Api\Sales\SalesmanDebtController::class, 'collect']);

// Return Order Settlements
Route::get('return-order-settlements/create', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'create']);
Route::get('return-order-settlements', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'index']);
Route::post('return-order-settlements', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'store']);
Route::get('return-order-settlements/{settlement}', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'show']);
Route::post('return-order-settlements/{settlement}/approve', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'approve']);
Route::post('return-order-settlements/{settlement}/cancel', [\App\Http\Controllers\Api\ReturnOrderSettlementController::class, 'cancel']);

Route::post('distribution-plans/{plan}/calculate', [\App\Http\Controllers\Api\Sales\DistributionPlanController::class, 'calculate']);
Route::post('distribution-plans/{plan}/approve', [\App\Http\Controllers\Api\Sales\DistributionPlanController::class, 'approve']);
Route::post('distribution-plans/{plan}/reopen', [\App\Http\Controllers\Api\Sales\DistributionPlanController::class, 'reopen']);
Route::put('distribution-plans/{plan}/customers/{customer}/qty', [\App\Http\Controllers\Api\Sales\DistributionPlanController::class, 'updateCustomerQty']);
Route::put('distribution-plans/{plan}/items/{item}/qty', [\App\Http\Controllers\Api\Sales\DistributionPlanController::class, 'updateItemQty']);

// Fix: vehicle-inventory-transaction-items has param name > 32 chars
if (class_exists(\App\Http\Controllers\Api\Fleet\VehicleInventoryTransactionItemController::class, false)) {
    Route::resource('vehicle-inventory-transaction-items', \App\Http\Controllers\Api\Fleet\VehicleInventoryTransactionItemController::class, [
        'parameters' => ['vehicle-inventory-transaction-items' => 'vi_item'],
    ]);
}

// ===== Restore & Force Delete Routes =====
$softDeleteResources = [
    'customers', 'companies', 'branches', 'users', 'roles', 'warehouses', 'warehouses-types',
    'treasuries', 'treasury-types', 'expense-types', 'expenses', 'items', 'item-categories', 'item-sub-categories', 'units',
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
    'collections', 'salesman-settlements', 'salesman-debts', 'customer-debts', 'customer-returns', 'customer-return-items',
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
    'vehicle-daily-expenses', 'vehicle-daily-shifts', 'vehicle-stock-counts', 'vehicle-stock-count-items',
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
Route::get('permissions/matrix', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'matrix']);
Route::get('permissions/check/{permission}', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'check']);
Route::post('permissions/check-batch', [\App\Http\Controllers\Api\Permissions\PermissionController::class, 'checkBatch']);

// ===== Role Custom Routes =====
Route::post('roles/{role}/permissions', [\App\Http\Controllers\Api\Permissions\RoleController::class, 'updatePermissions']);
Route::post('roles/copy-permissions', [\App\Http\Controllers\Api\Permissions\RoleController::class, 'copyPermissions']);

// ===== Opening Balance Document Custom Routes =====
Route::post('opening-balance-documents/{openingBalanceDocument}/post', [\App\Http\Controllers\Api\Accounting\OpeningBalanceDocumentController::class, 'post'])->middleware('permission:accounting.opening.post');
Route::post('opening-balance-documents/{openingBalanceDocument}/cancel', [\App\Http\Controllers\Api\Accounting\OpeningBalanceDocumentController::class, 'cancel'])->middleware('permission:accounting.opening.cancel');

// ===== Sales Invoice Custom Routes =====
Route::post('sales-invoices/{salesInvoice}/post', [\App\Http\Controllers\Api\Sales\SalesInvoiceController::class, 'post'])->middleware('permission:sales.invoice.post');
Route::post('sales-invoices/{salesInvoice}/cancel', [\App\Http\Controllers\Api\Sales\SalesInvoiceController::class, 'cancel'])->middleware('permission:sales.invoice.cancel');

// ===== Purchase Invoice Custom Routes =====
Route::post('purchase-invoices/{purchaseInvoice}/post', [\App\Http\Controllers\Api\Purchase\PurchaseInvoiceController::class, 'post'])->middleware('permission:purchase.invoice.post');
Route::post('purchase-invoices/{purchaseInvoice}/cancel', [\App\Http\Controllers\Api\Purchase\PurchaseInvoiceController::class, 'cancel'])->middleware('permission:purchase.invoice.cancel');

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

    $unitService = app(\App\Services\UnitConversionService::class);

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
            $conversionFactor = $unitService->getConversionFactor($itemId, $obItem->unit_id);
            $stockQty[$itemId][$whId] += (float)$obItem->qty * $conversionFactor;
        }
    }

    if ($warehouseId) {
        $stockQty = array_filter($stockQty, fn($whStocks) => isset($whStocks[$warehouseId]));
    }

    $result = $items->map(function ($item) use ($stockQty, $warehouses, $transactions, $unitService) {
        $itemStock = [];
        $totalQty = 0;
        foreach ($warehouses as $wh) {
            $qty = $stockQty[$item->id][$wh->id] ?? 0;
            $itemStock[$wh->id] = $qty;
            $totalQty += $qty;
        }

        $unitBreakdown = $unitService->breakdownQuantity($item->id, $totalQty);

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
        return response()->json(['message' => 'warehouse_id Ù…Ø·Ù„ÙˆØ¨'], 400);
    }

    $openingBalanceRecords = \App\Models\InventoryOpeningBalance::query()
        ->where('warehouse_id', $warehouseId)
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->get();

    $openingBalances = [];
    foreach ($openingBalanceRecords as $ob) {
        $itemId = $ob->item_id;
        $conversionFactor = 1;
        if (!empty($ob->unit_id)) {
            $iu = \App\Models\ItemUnit::where('item_id', $itemId)
                ->where('unit_id', $ob->unit_id)
                ->whereNull('deleted_at')
                ->first();
            if ($iu && $iu->conversion_factor > 0) $conversionFactor = $iu->conversion_factor;
        }
        $openingBalances[$itemId] = ($openingBalances[$itemId] ?? 0) + (float)$ob->qty * $conversionFactor;
    }

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
                'notes' => $incentiveQty > 0 ? "Ø­Ø§ÙØ²: $incentiveQty" : null,
            ]);
        }
    }

    return response()->json($loadRequest->load('items.item'), 201);
});

// Update Load Request Status
Route::patch('load-requests/{loadRequest}/status', [\App\Http\Controllers\Api\Fleet\LoadRequestController::class, 'updateStatus']);
Route::post('load-requests/{loadRequest}/approve', [\App\Http\Controllers\Api\Fleet\LoadRequestController::class, 'approve']);
Route::post('load-requests/{loadRequest}/reject', [\App\Http\Controllers\Api\Fleet\LoadRequestController::class, 'reject']);

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
Route::get('reports/tables', [\App\Http\Controllers\Api\Reports\ReportController::class, 'tables']);
Route::get('reports/tables/{table}/schema', [\App\Http\Controllers\Api\Reports\ReportController::class, 'tableSchema']);
Route::get('reports/templates', [\App\Http\Controllers\Api\Reports\ReportController::class, 'templates']);
Route::get('reports', [\App\Http\Controllers\Api\Reports\ReportController::class, 'index']);
Route::post('reports', [\App\Http\Controllers\Api\Reports\ReportController::class, 'store']);
Route::get('reports/{report}', [\App\Http\Controllers\Api\Reports\ReportController::class, 'show']);
Route::put('reports/{report}', [\App\Http\Controllers\Api\Reports\ReportController::class, 'update']);
Route::delete('reports/{report}', [\App\Http\Controllers\Api\Reports\ReportController::class, 'destroy']);
Route::post('reports/{report}/execute', [\App\Http\Controllers\Api\Reports\ReportController::class, 'execute']);
Route::post('reports/{report}/share', [\App\Http\Controllers\Api\Reports\ReportController::class, 'share']);

// ============================================================
// PHASE 6: INTEGRATION HUB
// ============================================================
$ic = \App\Http\Controllers\Api\Integration\IntegrationController::class;

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
$mc = \App\Http\Controllers\Api\Reports\MonitoringController::class;
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
// REP DAILY SETTLEMENTS (تسويات المندوبين اليومية)
// ============================================================
$rdsc = \App\Http\Controllers\Api\Sales\RepDailySettlementController::class;
Route::get('rep-daily-settlements', [$rdsc, 'index']);
Route::get('rep-daily-settlements/{repDailySettlement}', [$rdsc, 'show']);
Route::post('rep-daily-settlements/{repDailySettlement}/approve', [$rdsc, 'approve']);
Route::post('rep-daily-settlements/{repDailySettlement}/cancel', [$rdsc, 'cancel']);
Route::post('rep-daily-settlements/{repDailySettlement}/reopen', [$rdsc, 'reopen']);

// ============================================================
// PHASE 10: SUPER ADMIN PANEL
// ============================================================
$sc = \App\Http\Controllers\Api\Settings\SuperAdminController::class;
Route::get('super-admin/stats', [$sc, 'stats']);
Route::get('super-admin/health', [$sc, 'health']);
Route::get('super-admin/companies', [$sc, 'companies']);
Route::get('super-admin/companies/{company}', [$sc, 'companyShow']);
Route::put('super-admin/companies/{company}/subscription', [$sc, 'updateSubscription']);
Route::get('super-admin/plans', [$sc, 'plans']);

// ===== Vehicle Alert Custom Routes =====
Route::post('vehicle-alerts/{id}/mark-read', [\App\Http\Controllers\Api\Fleet\VehicleAlertController::class, 'markAsRead']);
Route::post('vehicle-alerts/{id}/resolve', [\App\Http\Controllers\Api\Fleet\VehicleAlertController::class, 'resolve']);

// ===== Vehicle Cost Report =====
Route::get('vehicle-cost-report', [\App\Http\Controllers\Api\Fleet\VehicleCostAnalysisController::class, 'costReport']);

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
