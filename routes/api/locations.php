<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\Inventory\DisplayLocationController::class, false)) {
    Route::apiResource('locations', \App\Http\Controllers\Api\Inventory\DisplayLocationController::class);
    Route::post('locations/{id}/restore', [\App\Http\Controllers\Api\Inventory\DisplayLocationController::class, 'restore']);
    Route::delete('locations/{id}/force-delete', [\App\Http\Controllers\Api\Inventory\DisplayLocationController::class, 'forceDelete']);
}
