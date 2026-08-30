<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Permissions\UserTypeController;

Route::apiResource('user-types', UserTypeController::class);
