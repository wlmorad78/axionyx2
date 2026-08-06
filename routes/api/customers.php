<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CRM\CustomerController;

Route::get('customers/import-template', [CustomerController::class, 'importTemplate']);
Route::get('customers/export', [CustomerController::class, 'export']);
Route::post('customers/import', [CustomerController::class, 'import']);
Route::post('customers/import-json', [CustomerController::class, 'importJson']);
Route::post('customers/last-invoices', [CustomerController::class, 'lastInvoices']);
Route::get('customers/{id}/ledger', [CustomerController::class, 'ledger']);
Route::apiResource('customers', CustomerController::class);