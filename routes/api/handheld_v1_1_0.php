<?php
/**
 * =====================================================================
 * Routes: Handheld V1.1.0 API
 * ---------------------------------------------------------------------
 * Description:
 * جميع نقاط النهاية (Endpoints) لواجهة الهاند هيلد الإصدار 1.1.0
 * المسار الأساسي: /api/handheld-v1/
 * =====================================================================
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HandheldV110Controller;

/*
|--------------------------------------------------------------------------
| Handheld V1.1.0 API Routes
|--------------------------------------------------------------------------
|
| المصادقة (Authentication): لا تتطلب Auth
| باقي الـ Endpoints: تتطلب Auth + day-closing middleware
|
*/

// ================================================================
// PUBLIC ROUTES (لا تتطلب مصادقة)
// ================================================================

// فحص صحة السيرفر
Route::get('handheld-v1/health', [HandheldV110Controller::class, 'health']);

// إصدار التطبيق
Route::get('handheld-v1/version', [HandheldV110Controller::class, 'version']);

// تسجيل الدخول
Route::post('handheld-v1/login', [HandheldV110Controller::class, 'login']);

// ================================================================
// PROTECTED ROUTES (تتطلب مصادقة Sanctum)
// ================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ------------------------------------------------------------
    // Authentication (المصادقة)
    // ------------------------------------------------------------
    Route::get('handheld-v1/profile', [HandheldV110Controller::class, 'profile']);
    Route::post('handheld-v1/refresh-token', [HandheldV110Controller::class, 'refreshToken']);
    Route::post('handheld-v1/logout', [HandheldV110Controller::class, 'logout']);

    // ------------------------------------------------------------
    // Customers (العملاء)
    // ------------------------------------------------------------
    Route::get('handheld-v1/customers', [HandheldV110Controller::class, 'customers']);
    Route::get('handheld-v1/customers/{id}', [HandheldV110Controller::class, 'customerDetails']);
    Route::get('handheld-v1/customers/{id}/statement', [HandheldV110Controller::class, 'customerStatement']);

    // ------------------------------------------------------------
    // End of Day (نهاية اليوم)
    // ------------------------------------------------------------
    Route::post('handheld-v1/end-day/submit', [HandheldV110Controller::class, 'endDaySubmit']);
    Route::get('handheld-v1/end-day/summary', [HandheldV110Controller::class, 'endDaySummary']);
    Route::get('handheld-v1/end-day/settlements', [HandheldV110Controller::class, 'endDaySettlements']);
    Route::get('handheld-v1/end-day/history', [HandheldV110Controller::class, 'endDayHistory']);

    // ------------------------------------------------------------
    // Sync (المزامنة)
    // ------------------------------------------------------------
    Route::post('handheld-v1/sync/push', [HandheldV110Controller::class, 'syncPush']);
    Route::post('handheld-v1/sync/pull', [HandheldV110Controller::class, 'syncPull']);
    Route::get('handheld-v1/sync/status', [HandheldV110Controller::class, 'syncStatus']);

    // ------------------------------------------------------------
    // General (عامة)
    // ------------------------------------------------------------
    Route::get('handheld-v1/salesman-profile', [HandheldV110Controller::class, 'salesmanProfile']);
    Route::get('handheld-v1/routes', [HandheldV110Controller::class, 'routes']);
    Route::get('handheld-v1/stock', [HandheldV110Controller::class, 'stock']);
    Route::get('handheld-v1/bank-accounts', [HandheldV110Controller::class, 'bankAccounts']);

});
