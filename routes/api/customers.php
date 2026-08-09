<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CRM\CustomerController;
use App\Http\Controllers\Api\Customers\CustomerOpeningBalanceController;

Route::get('customers/import-template', [CustomerController::class, 'importTemplate']);
Route::get('customers/export', [CustomerController::class, 'export']);
Route::post('customers/import', [CustomerController::class, 'import']);
Route::post('customers/import-json', [CustomerController::class, 'importJson']);
Route::post('customers/last-invoices', [CustomerController::class, 'lastInvoices']);
Route::get('customers/{id}/ledger', [CustomerController::class, 'ledger']);
Route::apiResource('customers', CustomerController::class);

Route::apiResource('customer-opening-balances', CustomerOpeningBalanceController::class);
Route::post('customer-opening-balances/{id}/restore', [CustomerOpeningBalanceController::class, 'restore']);