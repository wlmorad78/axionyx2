<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\StreetController;

Route::apiResource('streets', StreetController::class);
Route::post('streets/{id}/restore', [StreetController::class, 'restore']);
Route::delete('streets/{id}/force-delete', [StreetController::class, 'forceDelete']);
