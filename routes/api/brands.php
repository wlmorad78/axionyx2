<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('brands', \App\Http\Controllers\Api\BrandController::class);
Route::post('brands/{id}/restore', [\App\Http\Controllers\Api\BrandController::class, 'restore']);
Route::delete('brands/{id}/force-delete', [\App\Http\Controllers\Api\BrandController::class, 'forceDelete']);
