<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Distribution\src\Controllers\SalesmanController;

Route::prefix('v2')->group(function () {

    // ─── CRUD ──────────────────────────────────────────────────
    Route::apiResource('salesmen', SalesmanController::class);
    Route::post('salesmen/{salesman}/restore', [SalesmanController::class, 'restore']);
    Route::delete('salesmen/{salesman}/force-delete', [SalesmanController::class, 'forceDelete']);
    Route::get('salesmen/next-code', [SalesmanController::class, 'nextCode']);
    Route::get('salesmen/dropdown', [SalesmanController::class, 'dropdown']);

});
