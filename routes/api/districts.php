<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\DistrictController;

Route::apiResource('districts', DistrictController::class);
Route::post('districts/{id}/restore', [DistrictController::class, 'restore']);
Route::delete('districts/{id}/force-delete', [DistrictController::class, 'forceDelete']);