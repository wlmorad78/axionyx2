<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Customer\src\Controllers\CustomerController;
use App\Modules\Customer\src\Controllers\CustomerAccountController;
use App\Modules\Customer\src\Controllers\CustomerLedgerController;
use App\Modules\Customer\src\Controllers\CustomerImportController;
use App\Modules\Customer\src\Controllers\CustomerExportController;

Route::prefix('v2')->group(function () {

    // ─── CRUD ──────────────────────────────────────────────────
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/restore', [CustomerController::class, 'restore']);
    Route::delete('customers/{customer}/force-delete', [CustomerController::class, 'forceDelete']);
    Route::get('customers/next-code', [CustomerController::class, 'nextCode']);
    Route::get('customers/schema', [CustomerController::class, 'schema']);

    // ─── Accounts ──────────────────────────────────────────────
    Route::get('customers/accounts', [CustomerAccountController::class, 'index']);

    // ─── Ledger ────────────────────────────────────────────────
    Route::get('customers/{id}/ledger', [CustomerLedgerController::class, 'show']);

    // ─── Import ────────────────────────────────────────────────
    Route::get('customers/import-template', [CustomerImportController::class, 'template']);
    Route::post('customers/import', [CustomerImportController::class, 'csv']);
    Route::post('customers/import-json', [CustomerImportController::class, 'json']);

    // ─── Export ────────────────────────────────────────────────
    Route::get('customers/export', [CustomerExportController::class, 'csv']);

    // ─── Last Invoices ─────────────────────────────────────────
    Route::post('customers/last-invoices', function (\Illuminate\Http\Request $request) {
        $customerIds = $request->input('customer_ids', []);
        if (empty($customerIds)) {
            return response()->json(['data' => []]);
        }
        $companyId = $request->user()->company_id;
        $service = app(\App\Modules\Customer\src\Services\CustomerService::class);
        $result = $service->lastInvoices($customerIds, $companyId);
        return response()->json(['data' => $result]);
    });

});
