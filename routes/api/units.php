<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Inventory\UnitController;
use App\Http\Controllers\Api\Inventory\ItemUnitController;

Route::get('units/next-code', [UnitController::class, 'nextCode']);
Route::apiResource('units', UnitController::class);
Route::post('units/{id}/restore', [UnitController::class, 'restore']);
Route::delete('units/{id}/force-delete', [UnitController::class, 'forceDelete']);

Route::apiResource('item-units', ItemUnitController::class);