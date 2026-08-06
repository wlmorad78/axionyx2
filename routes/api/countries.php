<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\CountryController;

Route::apiResource('countries', CountryController::class);
Route::post('countries/{id}/restore', [CountryController::class, 'restore']);
Route::delete('countries/{id}/force-delete', [CountryController::class, 'forceDelete']);