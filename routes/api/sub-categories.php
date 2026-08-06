<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, false)) {
    Route::apiResource('sub-categories', \App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class);
    Route::post('sub-categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'restore']);
    Route::delete('sub-categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'forceDelete']);
}
