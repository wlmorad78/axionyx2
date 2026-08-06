<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Inventory\WarehouseController;

Route::apiResource('warehouses', WarehouseController::class);
Route::post('warehouses/{id}/restore', [WarehouseController::class, 'restore']);
Route::delete('warehouses/{id}/force-delete', [WarehouseController::class, 'forceDelete']);