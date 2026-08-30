<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('categories', \App\Http\Controllers\Api\Inventory\ItemCategoryController::class);
Route::post('categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'restore']);
Route::delete('categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'forceDelete']);

Route::apiResource('item-categories', \App\Http\Controllers\Api\Inventory\ItemCategoryController::class);
Route::post('item-categories/{id}/restore', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'restore']);
Route::delete('item-categories/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\ItemCategoryController::class, 'forceDelete']);
