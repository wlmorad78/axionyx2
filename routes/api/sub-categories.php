<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('sub-categories', \App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class);
Route::post('sub-categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'restore']);
Route::delete('sub-categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'forceDelete']);

Route::apiResource('item-sub-categories', \App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class);
Route::post('item-sub-categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'restore']);
Route::delete('item-sub-categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemSubCategoryController::class, 'forceDelete']);
