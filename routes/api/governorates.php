<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\GovernorateController;

Route::apiResource('governorates', GovernorateController::class);
Route::post('governorates/{id}/restore', [GovernorateController::class, 'restore']);
Route::delete('governorates/{id}/force-delete', [GovernorateController::class, 'forceDelete']);
