<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Suppliers\SupplierController;
use App\Http\Controllers\Api\Suppliers\SupplierOpeningBalanceController;

Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('supplier-opening-balances', SupplierOpeningBalanceController::class);
Route::post('supplier-opening-balances/{id}/restore', [SupplierOpeningBalanceController::class, 'restore']);