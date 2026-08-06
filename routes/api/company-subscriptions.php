<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Company\CompanySubscriptionController;

Route::apiResource('company-subscriptions', CompanySubscriptionController::class);
Route::post('company-subscriptions/{id}/restore', [CompanySubscriptionController::class, 'restore']);
Route::delete('company-subscriptions/{id}/force-delete', [CompanySubscriptionController::class, 'forceDelete']);
