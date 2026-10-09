<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\WorkDayController;
use App\Http\Controllers\Api\Settings\BranchTargetController;

Route::apiResource('work-days', WorkDayController::class);
Route::get('work-days-schema', [WorkDayController::class, 'schema']);

Route::apiResource('branch-targets', BranchTargetController::class);
Route::get('branch-targets-schema', [BranchTargetController::class, 'schema']);
