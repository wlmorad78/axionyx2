<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, false)) {
    Route::apiResource('categories', \App\Http\Controllers\Api\Inventory\ItemCategoryController::class);
    Route::post('categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'restore']);
    Route::delete('categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'forceDelete']);
}
