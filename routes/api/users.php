<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Permissions\UserController;
use App\Http\Controllers\Api\UserRoleController;

Route::apiResource('users', UserController::class);
Route::post('users/{id}/restore', [UserController::class, 'restore']);
Route::delete('users/{id}/force-delete', [UserController::class, 'forceDelete']);

Route::get('users/{id}/roles', [UserRoleController::class, 'index']);
Route::put('users/{id}/roles', [UserRoleController::class, 'update']);