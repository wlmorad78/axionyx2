<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Sync\Controllers\SyncController;

/*
|--------------------------------------------------------------------------
| Sync API Routes
|--------------------------------------------------------------------------
|
| Push/Pull invoices between local ↔ external server.
| Requires X-Sync-Token header for server-to-server auth.
|
*/

Route::prefix('api')->middleware('auth:sanctum')->group(function () {

    // Push invoice from this server to external
    Route::post('v2/sync/send-invoice', [SyncController::class, 'sendInvoice']);

    // Pull invoice from external to this server
    Route::post('v2/sync/pull-invoice', [SyncController::class, 'pullInvoice']);
});

// Receive invoice from another server (protected by X-Sync-Token, not Sanctum)
Route::prefix('api')->post('v2/sync/receive-invoice', [SyncController::class, 'receiveInvoice']);

// Export invoice data for another server (protected by X-Sync-Token)
Route::prefix('api')->get('v2/sync/export-invoice', [SyncController::class, 'exportInvoice']);
