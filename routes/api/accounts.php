<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Accounting\AccountController;

Route::apiResource('accounts', AccountController::class);
Route::post('accounts/{id}/restore', [AccountController::class, 'restore']);
Route::delete('accounts/{id}/force-delete', [AccountController::class, 'forceDelete']);