<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Settings\BranchController;

Route::apiResource('branches', BranchController::class);
Route::post('branches/{id}/restore', [BranchController::class, 'restore']);
Route::delete('branches/{id}/force-delete', [BranchController::class, 'forceDelete']);