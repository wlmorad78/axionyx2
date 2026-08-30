<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CRM\CustomerController;
use App\Http\Controllers\Api\Customers\CustomerOpeningBalanceController;

Route::get('sales-areas', function (Request $request) {
    $q = \App\Models\SalesTerritory::where('is_active', true);
    if ($companyId = $request->header('X-Company-Id')) {
        $q->where('company_id', $companyId);
    }
    return response()->json($q->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']));
});

Route::get('routes', function (Request $request) {
    $q = \App\Models\Route::where('is_active', true);
    if ($request->filled('sales_territory_id')) {
        $q->where('sales_territory_id', $request->sales_territory_id);
    }
    if ($companyId = $request->header('X-Company-Id')) {
        $q->where('company_id', $companyId);
    }
    return response()->json($q->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'code']));
});

Route::get('customers/import-template', [CustomerController::class, 'importTemplate']);
Route::get('customers/export', [CustomerController::class, 'export']);
Route::post('customers/import', [CustomerController::class, 'import']);
Route::post('customers/import-json', [CustomerController::class, 'importJson']);
Route::post('customers/last-invoices', [CustomerController::class, 'lastInvoices']);
Route::get('customers/accounts', [CustomerController::class, 'accounts']);
Route::get('customers/{id}/ledger', [CustomerController::class, 'ledger']);
Route::apiResource('customers', CustomerController::class);

Route::apiResource('customer-opening-balances', CustomerOpeningBalanceController::class);
Route::post('customer-opening-balances/{id}/restore', [CustomerOpeningBalanceController::class, 'restore']);
