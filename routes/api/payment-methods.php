<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Treasury\PaymentMethodController;

Route::apiResource('payment-methods', PaymentMethodController::class);
Route::post('payment-methods/{id}/restore', [PaymentMethodController::class, 'restore']);
Route::delete('payment-methods/{id}/force-delete', [PaymentMethodController::class, 'forceDelete']);
