<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Company\CompanySubscriptionLimitController;

Route::apiResource('company-subscription-limits', CompanySubscriptionLimitController::class);
Route::post('company-subscription-limits/{id}/restore', [CompanySubscriptionLimitController::class, 'restore']);
Route::delete('company-subscription-limits/{id}/force-delete', [CompanySubscriptionLimitController::class, 'forceDelete']);
