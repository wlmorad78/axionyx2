<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\Inventory\ItemController::class, false)) {
    Route::apiResource('products', \App\Http\Controllers\Api\Inventory\ItemController::class);
    Route::post('products/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'restore']);
    Route::delete('products/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemController::class, 'forceDelete']);
}
