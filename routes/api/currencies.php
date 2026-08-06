<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\CurrencyController;

Route::apiResource('currencies', CurrencyController::class);
Route::post('currencies/{id}/restore', [CurrencyController::class, 'restore']);
Route::delete('currencies/{id}/force-delete', [CurrencyController::class, 'forceDelete']);
