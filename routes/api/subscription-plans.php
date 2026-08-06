<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\SubscriptionPlanController;
use App\Http\Controllers\Api\Permissions\FeatureController;

Route::apiResource('subscription-plans', SubscriptionPlanController::class);
Route::get('subscription-plans/{subscriptionPlan}/matrix', [SubscriptionPlanController::class, 'matrix']);
Route::post('subscription-plans/{id}/restore', [SubscriptionPlanController::class, 'restore']);
Route::delete('subscription-plans/{id}/force-delete', [SubscriptionPlanController::class, 'forceDelete']);

Route::get('features', [FeatureController::class, 'index']);
Route::get('features/enabled', [FeatureController::class, 'enabled']);
Route::get('features/check/{code}', [FeatureController::class, 'check']);
Route::post('features/check-batch', [FeatureController::class, 'checkBatch']);
