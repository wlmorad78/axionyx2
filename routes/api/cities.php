<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\CityController;

Route::apiResource('cities', CityController::class);
Route::post('cities/{id}/restore', [CityController::class, 'restore']);
Route::delete('cities/{id}/force-delete', [CityController::class, 'forceDelete']);