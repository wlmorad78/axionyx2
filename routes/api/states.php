<?php

use Illuminate\Support\Facades\Route;

if (class_exists(\App\Http\Controllers\Api\StateController::class, false)) {
    Route::apiResource('states', \App\Http\Controllers\Api\StateController::class);
    Route::post('states/{id}/restore', [\App\Http\Controllers\Api\StateController::class, 'restore']);
    Route::delete('states/{id}/force-delete', [\App\Http\Controllers\Api\StateController::class, 'forceDelete']);
}
