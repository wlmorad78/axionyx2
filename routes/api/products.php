<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('products', \App\Http\Controllers\Api\Inventory\ItemController::class);
Route::post('products/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'restore']);
Route::delete('products/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'forceDelete']);

Route::apiResource('items', \App\Http\Controllers\Api\Inventory\ItemController::class);
Route::post('items/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'restore']);
Route::delete('items/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'forceDelete']);
