<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Handheld2Controller;

Route::post('handheld2/login', [Handheld2Controller::class, 'login']);
Route::get('handheld2/health', [Handheld2Controller::class, 'health']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('handheld2/salesman-profile', [Handheld2Controller::class, 'salesmanProfile']);
    Route::get('handheld2/start-day-counts', [Handheld2Controller::class, 'startDayCounts']);
    Route::get('handheld2/routes-with-customers', [Handheld2Controller::class, 'routesWithCustomers']);
    Route::get('handheld2/next-customer-code', [Handheld2Controller::class, 'nextCustomerCode']);
    Route::get('handheld2/download-data', [Handheld2Controller::class, 'downloadData']);
    Route::get('handheld2/load-orders', [Handheld2Controller::class, 'loadOrders']);
    Route::post('handheld2/sync/push', [Handheld2Controller::class, 'syncPush']);
    Route::post('handheld2/sync/pull', [Handheld2Controller::class, 'syncPull']);
});