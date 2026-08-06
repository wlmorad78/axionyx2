<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\SafeController::class, false)) {
    Route::apiResource('safes', \App\Http\Controllers\Api\SafeController::class);
    Route::post('safes/{id}/restore', [\App\Http\Controllers\Api\SafeController::class, 'restore']);
    Route::delete('safes/{id}/force-delete', [\App\Http\Controllers\Api\SafeController::class, 'forceDelete']);
}
